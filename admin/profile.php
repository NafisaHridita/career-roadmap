<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php';
require_once '../includes/module_helpers.php';
$userId=(int)$_SESSION['user_id']; $error=''; $message=$_SESSION['admin_profile_flash']??''; unset($_SESSION['admin_profile_flash']); $viewOnly=(($_GET['view']??'')==='1');
$uploadDir=__DIR__.'/../assets/uploads/admin_profiles';
if (!is_dir($uploadDir)) @mkdir($uploadDir,0755,true);
if (!isset($_SESSION['admin_profile_token'])) $_SESSION['admin_profile_token']=bin2hex(random_bytes(32));
$token=$_SESSION['admin_profile_token'];
if (!ensureAdminProfileTable($conn)) $error='Could not prepare profile storage. Check database permissions.';
if (!ensureAdminSocialLinksTable($conn)) $error='Could not prepare social links storage. Check database permissions.';

$userStmt=$conn->prepare("SELECT name,email FROM users WHERE user_id=? AND role='admin'");
$userStmt->bind_param('i',$userId); $userStmt->execute(); $admin=$userStmt->get_result()->fetch_assoc(); $userStmt->close();
if (!$admin) { header('Location: ../logout.php'); exit(); }

if ($_SERVER['REQUEST_METHOD']==='POST' && !$error) {
    if (!hash_equals($token,$_POST['csrf_token']??'')) $error='Session expired. Reload and try again.';
    elseif (($_POST['action']??'')==='delete_photo') {
        $stmt=$conn->prepare('SELECT photo_path FROM admin_profiles WHERE user_id=?'); $stmt->bind_param('i',$userId); $stmt->execute(); $old=$stmt->get_result()->fetch_assoc()['photo_path']??''; $stmt->close();
        if ($old!=='') {
            $candidate=realpath($uploadDir.DIRECTORY_SEPARATOR.basename($old));
            if ($candidate && is_file($candidate) && strpos($candidate,realpath($uploadDir).DIRECTORY_SEPARATOR)===0) @unlink($candidate);
        }
        $stmt=$conn->prepare('INSERT INTO admin_profiles (user_id,photo_path) VALUES (?,NULL) ON DUPLICATE KEY UPDATE photo_path=NULL'); $stmt->bind_param('i',$userId); $stmt->execute(); $stmt->close(); $message='Profile photo removed.';
    } else {
        $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $website=trim($_POST['website']??'');
        $socialRows=[]; $apps=$_POST['social_app']??[]; $customApps=$_POST['custom_app']??[]; $urls=$_POST['social_url']??[]; $usernames=$_POST['social_username']??[];
        $knownApps=['Instagram','Facebook','LinkedIn','GitHub','X','YouTube','TikTok','WhatsApp','Other'];
        foreach (is_array($apps)?$apps:[] as $i=>$app) {
            $app=trim((string)$app); $custom=trim((string)($customApps[$i]??'')); $url=trim((string)($urls[$i]??'')); $username=trim((string)($usernames[$i]??''));
            if ($app==='Other') $app=$custom;
            if ($app==='' && $url==='' && $username==='') continue;
            if ($app==='' || strlen($app)>80 || ($url==='' && $username==='')) { $error='Each social account needs an app name and either a URL or username.'; break; }
            if (!in_array(($apps[$i]??''),$knownApps,true)) { $error='Choose a listed social app or select Other.'; break; }
            if ($url!=='' && (!filter_var($url,FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true))) { $error='Enter a valid http(s) URL for each social account.'; break; }
            $socialRows[]=['app'=>$app,'url'=>$url,'username'=>$username];
        }
        if ($name==='') $error='Name is required.';
        elseif (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>255) $error='Enter a valid email address.';
        elseif ($phone!=='' && !preg_match('/^[0-9+() .-]{7,40}$/',$phone)) $error='Enter a valid phone number.';
        else {
            foreach (['website'=>$website] as $label=>$url) {
                if ($url!=='' && (!filter_var($url,FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true))) { $error="Enter a valid http(s) URL for {$label}."; break; }
            }
            $newPhoto='';
            $photoUpload = $_FILES['cropped_photo'] ?? $_FILES['profile_photo'] ?? null;
            if (!$error && $photoUpload && $photoUpload['error']!==UPLOAD_ERR_NO_FILE) {
                $file=$photoUpload;
                if ($file['error']!==UPLOAD_ERR_OK || $file['size']>3*1024*1024) $error='Photo upload failed or file is larger than 3 MB.';
                else {
                    $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']);
                    $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name'])===false) $error='Use a JPG, PNG, or WebP image.';
                    elseif (!is_dir($uploadDir) && !@mkdir($uploadDir,0755,true)) $error='Could not create the profile photo folder.';
                    else {
                        $filename=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
                        if (move_uploaded_file($file['tmp_name'],$uploadDir.DIRECTORY_SEPARATOR.$filename)) $newPhoto='assets/uploads/admin_profiles/'.$filename;
                        else $error='Could not save the uploaded photo.';
                    }
                }
            }
            if (!$error) {
                $stmt=$conn->prepare('SELECT photo_path FROM admin_profiles WHERE user_id=?'); $stmt->bind_param('i',$userId); $stmt->execute(); $oldPath=$stmt->get_result()->fetch_assoc()['photo_path']??null; $stmt->close();
                if ($newPhoto!=='') $photoPath=$newPhoto;
                else $photoPath=$oldPath;
                $stmt=$conn->prepare('UPDATE users SET name=?,email=? WHERE user_id=? AND role=\'admin\''); $stmt->bind_param('ssi',$name,$email,$userId); $saved=$stmt->execute(); $stmt->close();
                if ($saved) {
                    $stmt=$conn->prepare('INSERT INTO admin_profiles (user_id,phone,website,linkedin_url,github_url,photo_path) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE phone=VALUES(phone),website=VALUES(website),linkedin_url=VALUES(linkedin_url),github_url=VALUES(github_url),photo_path=VALUES(photo_path)');
                    $empty=''; $stmt->bind_param('isssss',$userId,$phone,$website,$empty,$empty,$photoPath); $saved=$stmt->execute(); $stmt->close();
                }
                if ($saved) {
                    $stmt=$conn->prepare('DELETE FROM admin_profile_social_links WHERE user_id=?'); $stmt->bind_param('i',$userId); $saved=$stmt->execute(); $stmt->close();
                    if ($saved && $socialRows) {
                        $stmt=$conn->prepare('INSERT INTO admin_profile_social_links (user_id,app_name,profile_url,username) VALUES (?,?,?,?)');
                        foreach ($socialRows as $social) { $appName=$social['app']; $socialUrl=$social['url']; $socialUsername=$social['username']; $stmt->bind_param('isss',$userId,$appName,$socialUrl,$socialUsername); if(!$stmt->execute()){$saved=false;break;} }
                        $stmt->close();
                    }
                }
                if ($saved) {
                    $_SESSION['name']=$name; $_SESSION['email']=$email; $_SESSION['admin_profile_flash']='Admin profile updated.';
                    if ($newPhoto!=='' && $oldPath) { $oldFile=realpath($uploadDir.DIRECTORY_SEPARATOR.basename($oldPath)); if($oldFile && is_file($oldFile) && strpos($oldFile,realpath($uploadDir).DIRECTORY_SEPARATOR)===0) @unlink($oldFile); }
                    header('Location: profile.php?view=1'); exit();
                } else { if($newPhoto!=='') @unlink($uploadDir.DIRECTORY_SEPARATOR.basename($newPhoto)); $error='Could not save profile details.'; }
            }
        }
    }
}
$stmt=$conn->prepare('SELECT phone,website,linkedin_url,github_url,photo_path FROM admin_profiles WHERE user_id=?'); $stmt->bind_param('i',$userId); $stmt->execute(); $profile=$stmt->get_result()->fetch_assoc()?:[]; $stmt->close();
$socialLinks=[]; $stmt=$conn->prepare('SELECT app_name,profile_url,username FROM admin_profile_social_links WHERE user_id=? ORDER BY link_id'); $stmt->bind_param('i',$userId); $stmt->execute(); $socialLinks=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
if (!$socialLinks) { if (!empty($profile['linkedin_url'])) $socialLinks[]=['app_name'=>'LinkedIn','profile_url'=>$profile['linkedin_url'],'username'=>'']; if (!empty($profile['github_url'])) $socialLinks[]=['app_name'=>'GitHub','profile_url'=>$profile['github_url'],'username'=>'']; }
$userStmt=$conn->prepare("SELECT name,email FROM users WHERE user_id=? AND role='admin'"); $userStmt->bind_param('i',$userId); $userStmt->execute(); $admin=$userStmt->get_result()->fetch_assoc(); $userStmt->close();
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Admin Profile</title><style>
*{box-sizing:border-box}body{font:16px Arial,sans-serif;background:#f0f2f5;color:#1c1e21;margin:0}.topbar{height:58px;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 max(22px,calc((100% - 900px)/2));box-shadow:0 1px 3px #0002}.topbar a{color:#5633a3;text-decoration:none;font-weight:bold}.page{max-width:900px;margin:28px auto 50px}.profile-card,.details{background:#fff;border-radius:14px;box-shadow:0 1px 3px #0002}.profile-card{text-align:center;padding:32px 24px 24px}.avatar-wrap{position:relative;width:178px;margin:0 auto 16px}.avatar{display:block;padding:0;overflow:hidden;width:178px;height:178px;border:5px solid #fff;border-radius:50%;object-fit:cover;background:#e4e6eb;box-shadow:0 2px 10px #0003;cursor:pointer}.avatar-placeholder{display:flex;align-items:center;justify-content:center;font-size:66px;color:#5633a3;font-weight:bold}.avatar-menu{position:absolute;z-index:5;left:50%;top:185px;transform:translateX(-50%);width:190px;padding:7px;background:#fff;border-radius:10px;box-shadow:0 4px 18px #0003}.avatar-menu[hidden]{display:none}.avatar-menu button{display:block;width:100%;text-align:left;background:#fff;color:#222;padding:11px;border-radius:6px}.avatar-menu button:hover{background:#f0f2f5}.avatar-menu .danger{color:#c62828}.profile-card h1{font-size:30px;margin:6px 0}.profile-card p{color:#65676b;margin:6px}.details{margin-top:18px;padding:26px 32px}.columns{display:grid;grid-template-columns:1fr 1fr;gap:0 24px}label{display:block;margin:12px 0;color:#36383b;font-weight:600}input{display:block;width:100%;padding:11px;margin-top:7px;border:1px solid #ccd0d5;border-radius:7px;font:inherit}button{padding:10px 16px;border:0;border-radius:7px;background:#5633a3;color:#fff;font-weight:bold;cursor:pointer}.actions{display:flex;gap:10px;align-items:center;margin-top:14px}.note{padding:12px;background:#e7f3ff;border-radius:7px}.read-details{line-height:1.8}.edit-link{display:inline-block;margin-top:12px;color:#5633a3;font-weight:bold}.hint{color:#65676b;font-size:14px}.hidden-file{display:none}dialog{border:0;border-radius:12px;padding:12px;max-width:90vw;box-shadow:0 6px 24px #0005}dialog::backdrop{background:#0009}dialog img{display:block;max-width:75vw;max-height:75vh}dialog button{margin-top:10px}@media(max-width:650px){.page{margin:14px}.columns{grid-template-columns:1fr}.details{padding:20px}.avatar,.avatar-wrap{width:148px;height:148px}.avatar-wrap{height:auto}.avatar-menu{top:155px}}
.page{max-width:780px}.profile-card{position:relative;overflow:hidden;border:1px solid #f1e7ff;background:linear-gradient(145deg,#fff 25%,#fbf5ff 100%);border-radius:24px;padding:34px 24px 28px}.profile-card:before{content:"✿   ✧   ♡";position:absolute;right:24px;top:18px;color:#c6a5ed;font-size:20px;letter-spacing:8px}.profile-card:after{content:"♡";position:absolute;left:26px;top:24px;color:#e3c9f4;font-size:28px}.avatar{border:6px solid white;box-shadow:0 0 0 4px #e6d4f7,0 8px 24px #8b64b533}.profile-card h1{color:#52317c}.profile-card p{color:#777080}.cute-tag{display:inline-block;padding:7px 13px;border-radius:20px;background:#f3e8ff;color:#70469c!important;font-size:13px;font-weight:bold;letter-spacing:.3px}.details{border:1px solid #f0e8f7;border-radius:20px}.details h2{color:#52317c}.read-details p{padding:12px 14px;margin:10px 0;background:#faf6ff;border-radius:10px}.edit-link{padding:10px 15px;border-radius:20px;background:#f0e6ff;text-decoration:none}.edit-link:hover{background:#e6d5fb;text-decoration:none}@media(max-width:650px){.profile-card:before{font-size:15px;letter-spacing:3px}.profile-card:after{font-size:20px}}\n</style><style>.avatar-menu{left:calc(100% + 12px);top:12px;transform:none}.social-editor{grid-column:1/-1;margin:12px 0}.social-row{display:grid;grid-template-columns:1fr 1.3fr 1fr auto;gap:8px;align-items:center;padding:10px;margin:10px 0;background:#faf6ff;border-radius:10px}.social-row input,.social-row select{min-width:0;margin:0;padding:9px;border:1px solid #d9cbe7;border-radius:7px}.social-row select{font:inherit}.secondary{background:#eee5f8;color:#52317c}.remove-social{padding:9px;background:#fbe8ee;color:#a22}.crop-dialog{width:min(94vw,620px);padding:22px}.crop-layout{display:grid;grid-template-columns:minmax(0,1fr) 180px;gap:18px;align-items:center}.crop-canvas{display:block;width:min(100%,500px);aspect-ratio:1;border-radius:12px;touch-action:none;background:#eee}.crop-tools label{margin:12px 0}.crop-tools select{width:100%;padding:9px}.crop-buttons{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}.crop-buttons .cancel{background:#eee5f8;color:#52317c}@media(max-width:650px){.avatar-menu{left:50%;top:calc(100% + 8px);transform:translateX(-50%)}.social-row{grid-template-columns:1fr}.crop-layout{grid-template-columns:1fr}.crop-canvas{width:min(100%,420px);margin:auto}.crop-tools{display:grid;grid-template-columns:1fr 1fr;gap:10px}}
</style><link rel="stylesheet" href="../assets/cute-theme.css">
</head><body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><div class="topbar"><a href="../index.php">CareerRoadmap</a><div> &nbsp; <a href="../logout.php">Logout</a></div></div><main class="page"><section class="profile-card <?= $viewOnly ? 'cute-view' : 'cute-edit' ?>"><p class="cute-tag">✨ Your admin space ✨</p><div class="avatar-wrap"><button type="button" id="avatarButton" class="avatar <?=empty($profile['photo_path'])?'avatar-placeholder':''?>" aria-label="Profile photo options"><?php if(!empty($profile['photo_path'])):?><img src="../<?=e($profile['photo_path'])?>" alt="Profile photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%"><?php else:?><?=e(strtoupper(substr($admin['name']??'A',0,1)))?><?php endif;?></button><div class="avatar-menu" id="avatarMenu" hidden><?php if(!empty($profile['photo_path'])):?><button type="button" id="viewPhoto">View photo</button><?php endif;?><?php if(!$viewOnly):?><button type="button" id="updatePhoto">Update photo</button><?php endif;?><?php if(!empty($profile['photo_path']) && !$viewOnly):?><form method="post" onsubmit="return confirm('Remove your profile photo?')"><input type="hidden" name="csrf_token" value="<?=e($token)?>"><button class="danger" name="action" value="delete_photo">Remove photo</button></form><?php endif;?></div></div><h1><?=e($admin['name']??'Admin')?></h1><p><?=e($admin['email']??'')?></p></section>
<dialog id="photoDialog"><?php if(!empty($profile['photo_path'])):?><img src="../<?=e($profile['photo_path'])?>" alt="Profile photo"><?php endif;?><button type="button" id="closePhoto">Close</button></dialog>
<dialog id="cropDialog" class="crop-dialog"><h2>Adjust profile photo</h2><p class="hint">Drag to position, zoom, and choose a frame.</p><div class="crop-layout"><canvas id="cropCanvas" class="crop-canvas" width="512" height="512"></canvas><div class="crop-tools"><label>Zoom<input id="cropZoom" type="range" min="1" max="3" step="0.01" value="1"></label><label>Frame<select id="cropFrame"><option value="none">No frame</option><option value="lavender">Lavender glow</option><option value="rose">Rose hearts</option><option value="gold">Golden frame</option><option value="mint">Mint cute</option><option value="daisy">Daisy garden</option><option value="stars">Little stars</option><option value="rainbow">Rainbow</option></select></label><label>Filter<select id="cropFilter"><option value="none">Original</option><option value="bright">Bright</option><option value="warm">Warm</option><option value="cool">Cool</option><option value="vintage">Vintage</option><option value="mono">Black &amp; white</option><option value="pink">Soft pink</option></select></label></div></div><div class="crop-buttons"><button type="button" class="cancel" id="cancelCrop">Cancel</button><button type="button" id="useCrop">Use photo</button></div></dialog><section class="details"><h2>Profile details</h2><p class="hint">Update your name, email, phone number, and profile links.</p><?php foreach([$message,$error] as $n):if($n):?><p class="note"><?=e($n)?></p><?php endif;endforeach;?>
<?php if ($viewOnly): ?><div class="read-details"><p><strong>Phone:</strong> <?=e($profile['phone']??'Not added')?> </p><p><strong>Website:</strong> <?php if(!empty($profile['website'])):?><a href="<?=e($profile['website'])?>" target="_blank" rel="noopener noreferrer"><?=e($profile['website'])?></a><?php else:?>Not added<?php endif;?></p><p><strong>Social accounts:</strong></p><?php if($socialLinks):foreach($socialLinks as $social):?><p><strong><?=e($social['app_name'])?>:</strong> <?php if(!empty($social['username'])):?>@<?=e(ltrim($social['username'],'@'))?> <?php endif;?><?php if(!empty($social['profile_url'])):?><a href="<?=e($social['profile_url'])?>" target="_blank" rel="noopener noreferrer">Open account</a><?php endif;?></p><?php endforeach;else:?><p>No social accounts added.</p><?php endif;?><a class="edit-link" href="profile.php?edit=1">Edit profile</a></div><?php else: ?><form id="profileForm" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=e($token)?>"><input type="hidden" name="action" value="save"><div class="columns"><label>Name<input name="name" maxlength="150" required value="<?=e($admin['name']??'')?>"></label><label>Email<input type="email" name="email" maxlength="255" required value="<?=e($admin['email']??'')?>"></label><label>Phone<input name="phone" maxlength="40" value="<?=e($profile['phone']??'')?>"></label><label>Personal website<input type="url" name="website" maxlength="255" value="<?=e($profile['website']??'')?>"></label><div class="social-editor"><h3>Social accounts</h3><p class="hint">Choose an app, then add its profile URL, username, or both.</p><div id="socialList"><?php $knownApps=['Instagram','Facebook','LinkedIn','GitHub','X','YouTube','TikTok','WhatsApp']; $rows=$socialLinks?:[['app_name'=>'','profile_url'=>'','username'=>'']]; foreach($rows as $social): $appName=$social['app_name']??''; $isOther=$appName!=='' && !in_array($appName,$knownApps,true); ?><div class="social-row"><select name="social_app[]" onchange="toggleCustomApp(this)"><option value="">Select app</option><?php foreach($knownApps as $app):?><option value="<?=e($app)?>" <?=$appName===$app?'selected':''?>><?=e($app)?></option><?php endforeach;?><option value="Other" <?=$isOther?'selected':''?>>Other</option></select><input name="custom_app[]" placeholder="App name if Other" value="<?=$isOther?e($appName):''?>" style="<?=$isOther?'':'display:none'?>"><input type="url" name="social_url[]" placeholder="Profile URL" value="<?=e($social['profile_url']??'')?>"><input name="social_username[]" maxlength="120" placeholder="Username" value="<?=e($social['username']??'')?>"><button type="button" class="remove-social" onclick="this.parentElement.remove()">Remove</button></div><?php endforeach;?></div><button type="button" id="addSocial" class="secondary">+ Add social account</button><template id="socialTemplate"><div class="social-row"><select name="social_app[]" onchange="toggleCustomApp(this)"><option value="">Select app</option><option>Instagram</option><option>Facebook</option><option>LinkedIn</option><option>GitHub</option><option>X</option><option>YouTube</option><option>TikTok</option><option>WhatsApp</option><option>Other</option></select><input name="custom_app[]" placeholder="App name if Other" style="display:none"><input type="url" name="social_url[]" placeholder="Profile URL"><input name="social_username[]" maxlength="120" placeholder="Username"><button type="button" class="remove-social" onclick="this.parentElement.remove()">Remove</button></div></template></div></div><input class="hidden-file" id="photoFile" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp"><input class="hidden-file" id="croppedFile" type="file" name="cropped_photo" accept="image/png"><p class="hint">Photo: JPG, PNG, or WebP, up to 3 MB. Click the photo above for options.</p><div class="actions"><button type="submit">Save profile details</button><a href="dashboard.php">Cancel</a></div></form><?php endif; ?></section></main>
<script>
const avatarButton=document.getElementById('avatarButton');
const avatarMenu=document.getElementById('avatarMenu');
const photoFile=document.getElementById('photoFile');
const croppedFile=document.getElementById('croppedFile');
const photoDialog=document.getElementById('photoDialog');
const cropDialog=document.getElementById('cropDialog');
const cropCanvas=document.getElementById('cropCanvas');
const cropCtx=cropCanvas.getContext('2d');
let sourceImage=null,baseScale=1,zoom=1,offsetX=0,offsetY=0,dragging=false,dragStartX=0,dragStartY=0,startOffsetX=0,startOffsetY=0;
avatarButton.addEventListener('click',()=>{avatarMenu.hidden=!avatarMenu.hidden});
const updatePhoto=document.getElementById('updatePhoto');
if(updatePhoto) updatePhoto.addEventListener('click',()=>photoFile.click());
if(photoFile)photoFile.addEventListener('change',()=>{
  if(!photoFile.files.length)return;
  sourceImage=new Image();
  sourceImage.onload=()=>{baseScale=Math.max(512/sourceImage.naturalWidth,512/sourceImage.naturalHeight);zoom=1;document.getElementById('cropZoom').value='1';offsetX=(512-sourceImage.naturalWidth*baseScale)/2;offsetY=(512-sourceImage.naturalHeight*baseScale)/2;drawCrop();avatarMenu.hidden=true;cropDialog.showModal();};
  sourceImage.src=URL.createObjectURL(photoFile.files[0]);
});
function drawCrop(){if(!sourceImage)return;const scale=baseScale*zoom,filters={none:'none',bright:'brightness(1.12) saturate(1.18)',warm:'sepia(.22) saturate(1.25)',cool:'saturate(1.12) hue-rotate(15deg)',vintage:'sepia(.42) contrast(.92) saturate(.82)',mono:'grayscale(1)',pink:'saturate(1.2) hue-rotate(315deg) brightness(1.04)'};cropCtx.clearRect(0,0,512,512);cropCtx.fillStyle='#fff';cropCtx.fillRect(0,0,512,512);cropCtx.filter=filters[document.getElementById('cropFilter').value]||'none';cropCtx.drawImage(sourceImage,offsetX,offsetY,sourceImage.naturalWidth*scale,sourceImage.naturalHeight*scale);cropCtx.filter='none';const frame=document.getElementById('cropFrame').value;if(frame==='lavender'||frame==='rose'||frame==='gold'||frame==='mint'||frame==='daisy'){const colors={lavender:'#a879d4',rose:'#e889a8',gold:'#d8ad42',mint:'#71b99f',daisy:'#f0bb56'};cropCtx.save();cropCtx.strokeStyle=colors[frame];cropCtx.lineWidth=22;cropCtx.strokeRect(12,12,488,488);if(frame==='lavender'){cropCtx.strokeStyle='#eadcf7';cropCtx.lineWidth=5;cropCtx.strokeRect(29,29,454,454);}if(frame==='rose'){cropCtx.fillStyle='#d96c91';cropCtx.font='38px Arial';[[30,68],[475,68],[30,495],[475,495]].forEach(p=>cropCtx.fillText('♡',p[0],p[1]));}if(frame==='mint'){cropCtx.fillStyle='#438c72';cropCtx.font='28px Arial';[[35,61],[465,61],[35,492],[465,492]].forEach(p=>cropCtx.fillText('✿',p[0],p[1]));}if(frame==='daisy'){[[48,48],[464,48],[48,464],[464,464]].forEach(p=>{for(let a=0;a<8;a++){const t=a*Math.PI/4;cropCtx.beginPath();cropCtx.fillStyle='#fff8dc';cropCtx.arc(p[0]+Math.cos(t)*13,p[1]+Math.sin(t)*13,7,0,Math.PI*2);cropCtx.fill();}cropCtx.beginPath();cropCtx.fillStyle='#f2c94c';cropCtx.arc(p[0],p[1],7,0,Math.PI*2);cropCtx.fill();});}cropCtx.restore();}if(frame==='stars'){cropCtx.fillStyle='#f6d76b';cropCtx.font='34px Arial';[[28,55],[460,55],[28,495],[460,495],[248,35],[248,500]].forEach(p=>cropCtx.fillText('✦',p[0],p[1]));}if(frame==='rainbow'){const colors=['#ed6a5a','#f4a261','#e9c46a','#73bda8','#6baed6','#a879d4'];cropCtx.save();colors.forEach((color,i)=>{cropCtx.strokeStyle=color;cropCtx.lineWidth=6;cropCtx.strokeRect(8+i*7,8+i*7,496-i*14,496-i*14);});cropCtx.restore();}}
document.getElementById('cropZoom').addEventListener('input',event=>{zoom=parseFloat(event.target.value);const scale=baseScale*zoom;offsetX=(512-sourceImage.naturalWidth*scale)/2;offsetY=(512-sourceImage.naturalHeight*scale)/2;drawCrop();});
document.getElementById('cropFrame').addEventListener('change',drawCrop);document.getElementById('cropFilter').addEventListener('change',drawCrop);
cropCanvas.addEventListener('pointerdown',event=>{dragging=true;cropCanvas.setPointerCapture(event.pointerId);dragStartX=event.clientX;dragStartY=event.clientY;startOffsetX=offsetX;startOffsetY=offsetY;});
cropCanvas.addEventListener('pointermove',event=>{if(!dragging)return;const rect=cropCanvas.getBoundingClientRect(),scale=baseScale*zoom;offsetX=Math.min(0,Math.max(512-sourceImage.naturalWidth*scale,startOffsetX+(event.clientX-dragStartX)*512/rect.width));offsetY=Math.min(0,Math.max(512-sourceImage.naturalHeight*scale,startOffsetY+(event.clientY-dragStartY)*512/rect.height));drawCrop();});
cropCanvas.addEventListener('pointerup',()=>{dragging=false});
document.getElementById('cancelCrop').addEventListener('click',()=>{cropDialog.close();photoFile.value='';sourceImage=null;});
document.getElementById('useCrop').addEventListener('click',()=>{cropCanvas.toBlob(blob=>{if(!blob)return;const transfer=new DataTransfer();transfer.items.add(new File([blob],'profile-photo.png',{type:'image/png'}));croppedFile.files=transfer.files;cropDialog.close();document.getElementById('profileForm').requestSubmit();},'image/png');});
const viewPhoto=document.getElementById('viewPhoto');if(viewPhoto)viewPhoto.addEventListener('click',()=>{avatarMenu.hidden=true;photoDialog.showModal();});
document.getElementById('closePhoto').addEventListener('click',()=>photoDialog.close());
document.addEventListener('click',event=>{if(!event.target.closest('.avatar-wrap'))avatarMenu.hidden=true;});
function toggleCustomApp(select){const custom=select.parentElement.querySelector('input[name="custom_app[]"]');custom.style.display=select.value==='Other'?'block':'none';if(select.value!=='Other')custom.value='';}
const socialList=document.getElementById('socialList'),socialTemplate=document.getElementById('socialTemplate'),addSocial=document.getElementById('addSocial');
if(addSocial)addSocial.addEventListener('click',()=>socialList.appendChild(socialTemplate.content.cloneNode(true)));
</script></body></html>
