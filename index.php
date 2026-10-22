<?php
// ============================================================
//  ★☆★ 野獣先輩の部屋 (やられサイト版) ★☆★
//  !!! 意図的に脆弱です。本番環境・大事なデータのあるサーバーに置かないこと !!!
//
//  仕込んである穴(探してみてね):
//   1. XSS(保存型)   : 掲示板の名前・本文を無加工で出力
//   2. XSS(反射型)   : 検索ボックス ?q= をそのまま表示
//   3. SQLインジェクション: 掲示板の投稿・検索・管理者ログイン
//   4. LFI           : ?page= を include に直結 (例: ?page=../index)
//   5. extract($_REQUEST): register_globals 時代の再現 (変数の上書き)
//   6. 管理者判定がCookie(admin=1)だけ / パスワード平文・ハードコード
//   7. 管理者パスワードが "1919810" / data/ が丸見え
//   8. エラーを全表示(情報漏えい)
// ============================================================
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 1);

$IMG = 'https://static.wikitide.net/chaosbattlewiki/c/c0/1999%E5%B9%B4%E3%81%AE%E3%82%A4%E3%83%B3%E3%82%BF%E3%83%93%E3%83%A5%E3%83%BC.jpeg';
$ADMIN_PASS = '1919810';

// 穴5: register_globals 再現（$_GET/$_POST/$_COOKIE が全部ただの変数になる）
extract($_REQUEST);
extract($_COOKIE);

$dir = dirname(__FILE__) . '/data';
if (!is_dir($dir)) { mkdir($dir, 0777); }

// ---------- DB (SQLite) ----------
$db = new PDO('sqlite:' . $dir . '/yajuu.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$db->exec("CREATE TABLE IF NOT EXISTS bbs (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, msg TEXT, date TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT, pass TEXT)");
if ($db->query("SELECT COUNT(*) FROM users")->fetchColumn() == 0) {
    $db->exec("INSERT INTO users (name, pass) VALUES ('admin', '$ADMIN_PASS')");
    $db->exec("INSERT INTO users (name, pass) VALUES ('yajuu', 'inmu1145141919810')");
}

// ---------- カウンタ ----------
$cntfile = $dir . '/count.txt';
$cnt = (int)@file_get_contents($cntfile) + 1;
@file_put_contents($cntfile, $cnt);

// ---------- 管理者ログイン (穴3,6) ----------
$msg_admin = '';
if ($mode == 'login') {
    $r = $db->query("SELECT * FROM users WHERE name='$u' AND pass='$p'");
    if ($r && $r->fetch()) {
        setcookie('admin', '1');   // Cookieに admin=1 を置くだけ
        header('Location: index.php#bbs'); exit;
    }
    $msg_admin = "ログイン失敗: $u";   // 穴1: ユーザー名を無加工で反射
}
if ($mode == 'logout') { setcookie('admin', '', time() - 3600); header('Location: index.php'); exit; }
$is_admin = ($_COOKIE['admin'] == 1);

// ---------- 削除 (管理者のみ…のはず) ----------
if ($mode == 'del' && $is_admin) {
    $db->exec("DELETE FROM bbs WHERE id=$id");     // 穴3
    header('Location: index.php#bbs'); exit;
}

// ---------- 書き込み (穴1,3) ----------
if ($mode == 'write') {
    if ($name == '') { $name = '名無しの野獣'; }
    $date = date('Y/m/d H:i');
    $db->exec("INSERT INTO bbs (name, msg, date) VALUES ('$name', '$msg', '$date')");
    header('Location: index.php#bbs'); exit;
}

// ---------- ページ読み込み (穴4) ----------
if (isset($page)) {
    include('pages/' . $page . '.php');
    exit;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>★野獣先輩の部屋★ ～やりますねぇ！～</title>
<style type="text/css">
<!--
body { background-color:#000080; color:#FFFF00; font-family:"MS Pゴシック",sans-serif; font-size:14px; }
a:link { color:#00FFFF; } a:visited { color:#FF99FF; } a:hover { color:#FF0000; background-color:#FFFF00; }
td.box { background-color:#000040; border:2px ridge #FFD700; padding:6px; }
-->
</style>
<script language="JavaScript">
<!--
var msgs = new Array("やりますねぇ！","いいよ、こいよ！","暑いですねぇ！","ファッ！？","たまげたなぁ","迫真","1919");
function yarimasu() { alert(msgs[Math.floor(Math.random()*msgs.length)]); }
// -->
</script>
</head>
<body bgcolor="#000080" text="#FFFF00" link="#00FFFF" vlink="#FF99FF" alink="#FF0000">
<center>
<marquee scrollamount="8" bgcolor="#FF0000"><font color="#FFFFFF" size="4"><b>★☆★ やりますねぇ！ ★☆★ いいよ、こいよ！ ★☆★ 暑いですねぇ！ ★☆★ ファッ！？ ★☆★ 迫真 ★☆★</b></font></marquee><br>
<font size="6"><b><font color="#FF0000">野</font><font color="#FF9900">獣</font><font color="#FFFF00">先</font><font color="#00FF00">輩</font><font color="#00FFFF">の</font><font color="#FF00FF">部</font><font color="#FFFFFF">屋</font></b></font><br>
<blink><font color="#FF0000" size="5">NEW!!</font></blink> <font size="2">(セキュリティは)ないです</font><br><br>

<table border="0" cellpadding="0" cellspacing="0"><tr><td bgcolor="#FFD700" style="padding:4px;">
<img src="<?php echo $IMG; ?>" alt="1999年のインタビュー" width="320" border="0" onerror="this.alt='(画像が読み込めませんでした…ウーン)';">
</td></tr></table>
<font size="2">▲ 1999年のインタビュー風景</font><br><br>

<table border="0" cellpadding="4" cellspacing="4" width="640">
<tr><td class="box" align="center">
  あなたは <font color="#00FF00" size="5"><b><?php echo str_pad($cnt, 6, '0', STR_PAD_LEFT); ?></b></font> 人目の野獣です。
</td></tr>

<tr><td class="box">
  <b><font color="#FF9900">■ 野獣検索</font></b><br>
  <form method="get" action="index.php">
  <input type="text" name="q" size="30" value=""> <input type="submit" value="検索">
  </form>
  <?php if (isset($q)) { ?>
    <hr size="1">
    「<?php echo $q; ?>」の検索結果:<br>
    <?php
    $r = $db->query("SELECT * FROM bbs WHERE msg LIKE '%$q%'");
    $found = 0;
    if ($r) { foreach ($r as $row) { $found++; echo "・" . $row['name'] . ": " . $row['msg'] . "<br>"; } }
    if (!$found) { echo "見つかりませんでした(ファッ！？)"; }
    ?>
  <?php } ?>
</td></tr>

<tr><td class="box">
  <b><font color="#FF9900">■ コンテンツ</font></b><br><br>
  ・<a href="#bbs">野獣BBS</a><br>
  ・<a href="index.php?page=meigen">野獣名言集</a><br>
  ・<a href="javascript:yarimasu()">やりますねぇボタン</a><br>
</td></tr>
</table>
<br>

<a name="bbs"></a>
<table border="0" cellpadding="4" cellspacing="4" width="640">
<tr><td class="box">
  <center><font size="5" color="#FF9900"><b>★野獣BBS★</b></font></center><br>
  <form method="post" action="index.php">
  <input type="hidden" name="mode" value="write">
  おなまえ <input type="text" name="name" size="24"><br>
  めっせーじ<br><textarea name="msg" cols="48" rows="4"></textarea><br>
  <input type="submit" value=" 書き込む(迫真) ">
  </form>
  <hr color="#FFD700">
  <?php
  $rows = $db->query("SELECT * FROM bbs ORDER BY id DESC");
  $n = 0;
  if ($rows) {
    foreach ($rows as $row) {
        $n++;
  ?>
    <table border="0" width="100%" cellpadding="2"><tr><td bgcolor="#000080">
      <font color="#00FF00">[<?php echo $row['id']; ?>]</font>
      <b><font color="#FF99FF"><?php echo $row['name']; ?></font></b>
      <font size="1" color="#AAAAAA">(<?php echo $row['date']; ?>)</font>
      <?php if ($is_admin) { ?><a href="index.php?mode=del&id=<?php echo $row['id']; ?>">[削除]</a><?php } ?><br>
      <?php echo $row['msg']; ?>
    </td></tr></table><br>
  <?php } } if (!$n) { echo '<font color="#AAAAAA">まだ書き込みがありません</font>'; } ?>
</td></tr>
</table>
<br>

<table border="0" cellpadding="4" cellspacing="4" width="640">
<tr><td class="box">
  <b><font color="#FF9900">■ 管理者ログイン</font></b><br>
  <?php if ($is_admin) { ?>
    ログイン中です。 <a href="index.php?mode=logout">ログアウト</a>
  <?php } else { ?>
    <?php if ($msg_admin) { echo '<font color="#FF0000">' . $msg_admin . '</font><br>'; } ?>
    <form method="post" action="index.php">
    <input type="hidden" name="mode" value="login">
    ID <input type="text" name="u" size="12"> PASS <input type="password" name="p" size="12">
    <input type="submit" value="ログイン">
    </form>
  <?php } ?>
</td></tr>
</table>
<br>
<font size="2">このサイトはわざと脆弱です(やられサイト)。壊れても乗っ取られても自己責任でどうぞ。<br>
Copyright (C) 1999-2026 野獣先輩の部屋</font>
</center>
</body>
</html>
