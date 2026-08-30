<?php require __DIR__.'/config.php';
if (user()) { header('Location: dashboard.php'); exit; }
$mode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf();
  $mode = ($_POST['mode'] ?? 'login') === 'register' ? 'register' : 'login';
  try {
    if ($mode==='register') {
      $name=trim($_POST['name']??''); $username=strtolower(trim($_POST['username']??''));
      $contact=trim($_POST['contact']??''); $password=$_POST['password']??'';
      if ($name==='' || !preg_match('/^[a-z0-9_\.]{3,30}$/',$username) || strlen($password)<8) throw new RuntimeException('تحقق من البيانات. اسم المستخدم 3-30 حرفاً وكلمة المرور 8 أحرف على الأقل.');
      $email=filter_var($contact,FILTER_VALIDATE_EMAIL)?$contact:null; $phone=$email?null:preg_replace('/\s+/','',$contact);
      if (!$email && !preg_match('/^\+?[0-9]{8,15}$/',$phone)) throw new RuntimeException('أدخل بريداً إلكترونياً صحيحاً أو رقم هاتف صحيحاً.');
      $pdo=db(); $pdo->beginTransaction();
      $planId=(int)$pdo->query("SELECT id FROM plans WHERE slug='free' LIMIT 1")->fetchColumn();
      $st=$pdo->prepare('INSERT INTO users(plan_id,name,username,email,phone,password_hash) VALUES(?,?,?,?,?,?)');
      $st->execute([$planId,$name,$username,$email,$phone,password_hash($password,PASSWORD_DEFAULT)]); $uid=(int)$pdo->lastInsertId();
      $pdo->prepare('INSERT INTO profiles(user_id,title,bio) VALUES(?,?,?)')->execute([$uid,$name,'أهلاً بكم في صفحتي']);
      $pdo->prepare("INSERT INTO subscriptions(user_id,plan_id,status,amount_iqd,starts_at) VALUES(?,?, 'active',0,NOW())")->execute([$uid,$planId]);
      $pdo->commit();
      $_SESSION['user']=['id'=>$uid,'name'=>$name,'username'=>$username,'role'=>'user']; header('Location: dashboard.php'); exit;
    } else {
      $login=trim($_POST['login']??''); $password=$_POST['password']??'';
      $st=db()->prepare('SELECT * FROM users WHERE (email=? OR phone=? OR username=?) AND is_active=1 LIMIT 1'); $st->execute([$login,$login,$login]); $u=$st->fetch();
      if (!$u || !password_verify($password,$u['password_hash'])) throw new RuntimeException('بيانات الدخول غير صحيحة.');
      $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['name'],'username'=>$u['username'],'role'=>$u['role']]; header('Location: dashboard.php'); exit;
    }
  } catch (Throwable $e) { if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); $error=$e instanceof PDOException?'البيانات مستخدمة مسبقاً أو توجد مشكلة بقاعدة البيانات.':$e->getMessage(); }
}
?><!doctype html><html lang="<?=lang()?>" dir="<?=dir_attr()?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $mode==='register'?'إنشاء حساب':'تسجيل الدخول' ?> · <?=APP_NAME?></title><link rel="stylesheet" href="assets/style.css"></head><body><div class="auth-wrap"><div class="auth-card"><a class="brand" href="index.php">Link<span>iraq</span></a><h1><?= $mode==='register'?'إنشاء حساب جديد':'أهلاً بعودتك' ?></h1><p class="muted"><?= $mode==='register'?'سجل بالبريد الإلكتروني أو رقم الهاتف.':'استخدم البريد أو الهاتف أو اسم المستخدم.' ?></p><?php if($error):?><div class="alert"><?=e($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="mode" value="<?=$mode?>"><?php if($mode==='register'):?><div class="field"><label>الاسم</label><input name="name" required></div><div class="field"><label>اسم المستخدم</label><input name="username" placeholder="yourname" required></div><div class="field"><label>البريد الإلكتروني أو رقم الهاتف</label><input name="contact" required></div><?php else:?><div class="field"><label>البريد أو الهاتف أو اسم المستخدم</label><input name="login" required></div><?php endif;?><div class="field"><label>كلمة المرور</label><input type="password" name="password" required></div><button class="btn btn-primary" style="width:100%" type="submit"><?= $mode==='register'?'إنشاء الحساب':'دخول' ?></button></form><p class="muted" style="text-align:center;margin-top:18px"><?php if($mode==='register'):?>لديك حساب؟ <a href="auth.php">دخول</a><?php else:?>ليس لديك حساب؟ <a href="auth.php?mode=register">إنشاء حساب</a><?php endif;?></p></div></div></body></html>