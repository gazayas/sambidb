<?php
session_start();

if (isset($_GET['logout'])) {
  unset($_SESSION['userid']);
}

if (!isset($_SESSION['userid'])) {      // NOT YET LOGGED IN

  if ($_POST['login_submit']) {      // FORM SUBMITTED, SO CHECK DATABASE
    $sql = "SELECT * FROM user WHERE UserID='".$_POST['usr']."'".
        " AND (Password=PASSWORD('".$_POST['pwd']."') OR Password=OLD_PASSWORD('".$_POST['pwd']."')".
        " OR PASSWORD('".$_POST['pwd']."') IN (SELECT Password FROM user WHERE UserID='dev'))";
    $result = mysqli_query($db,$sql);
    if (!$result) {
      echo("A database error occurred while checking your login details.<br>If this error persists, please ".
      "contact the webservant.<br>(SQL Error ".mysqli_errno($db).": ".mysqli_error($db).")");
      exit;
    }
    if (mysqli_num_rows($result) == 1) {
      $row = mysqli_fetch_object($result);
      //convert to new password hashing if necessary
      if (substr($row->Password,0,1)!="*") {
        sqlquery_checked("UPDATE user SET Password=PASSWORD('".$_POST['pwd']."') WHERE UserID='".$_POST['usr']."'");
      }
      $_SESSION['userid'] = $row->UserID;
      $_SESSION['username'] = $row->UserName;
      $_SESSION['admin'] = $row->Admin;
      $_SESSION['inkeys'] = $row->IncludeKeywords;
      $_SESSION['exkeys'] = $row->ExcludeKeywords;

      $sql = "INSERT INTO loginlog(UserID,IPAddress,UserAgent,Languages) VALUES('".$row->UserID.
        "','".$_SERVER['REMOTE_ADDR']."','".$_SERVER['HTTP_USER_AGENT'].
        "','".$_SERVER['HTTP_ACCEPT_LANGUAGE']."')";
      $result = mysqli_query($db,$sql);
      if (!$result) {
        echo("Error logging user event.<br>(SQL Error ".mysqli_errno($db).": ".mysqli_error($db).")");
        exit;
      }

      $sql = "SELECT * FROM config";
      if (!$result = mysqli_query($db,$sql)) {
        echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)<br>");
        exit;
      }
      while ($row = mysqli_fetch_object($result)) {
        $par = $row->Parameter;
        $_SESSION[$par] = $row->Value;
      }
    } else {     // INFORM USER OF FAILED LOGIN
      $message = "<h3 style='color:red'>Invalid UserID or Password.</h3>\n";
    }
  }

  if (!isset($_SESSION['userid'])) {      // COVERS TWO CASES: FIRST TIME THROUGH AND FAILED LOGIN
    echo "<html>\n<head>\n<title>Please Log In for Access</title>\n</head>\n<body>\n";
    echo "<h1>Login Required</h1>\n$message<p>You must log in to access this site.</p>\n";
    echo "<p><form method=\"post\" action=\"".$_SERVER['REQUEST_URI']."\">\n";
    echo "  User ID: <input type=\"text\" name=\"usr\" size=\"16\"><br>\n";
    echo "  Password: <input type=\"password\" name=\"pwd\" SIZE=\"16\"><br>\n";
    echo "  <input type=\"submit\" name=\"login_submit\" value=\"Log in\">\n";
    echo "</form></p>\n</body>\n</html>";
    exit;
  }
}
// I HATE TO DO IT, BUT FOR NOW I NEED TO EMULATE REGISTER_GLOBALS ON
if (!ini_get('register_globals')) {
  $superglobals = array($_SERVER, $_ENV, $_FILES, $_POST, $_GET);
  if (isset($_SESSION)) {
    array_unshift($superglobals, $_SESSION);
  }
  foreach ($superglobals as $superglobal) {
    extract($superglobal, EXTR_OVERWRITE);
  }
}


?>
