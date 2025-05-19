<?php
// Debugging code - remove after fixing
$include_path = __DIR__ . '/components/connect.php';
if (!file_exists($include_path)) {
    die("Error: connect.php not found at $include_path");
}
include $include_path;

// Debugging code - remove after fixing
include 'components/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   $user_id = '';
}

if(isset($_POST['submit'])){

   $id = create_unique_id();
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING); 
   $number = $_POST['number'];
   $number = filter_var($number, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING); 
   $c_pass = sha1($_POST['c_pass']);
   $c_pass = filter_var($c_pass, FILTER_SANITIZE_STRING);   

   // Check if email exists
   $select_users = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
   $select_users->execute([$email]);

   if($select_users->rowCount() > 0){
      $warning_msg[] = 'Email already taken!';
   }else{
      // Compare original passwords before hashing
      if($_POST['pass'] != $_POST['c_pass']){
         $warning_msg[] = 'Password not matched!';
      }else{
         // Insert new user
         $insert_user = $conn->prepare("INSERT INTO `users`(id, name, number, email, password) VALUES(?,?,?,?,?)");
         $success = $insert_user->execute([$id, $name, $number, $email, $pass]);
         
         if($success){
            setcookie('user_id', $id, time() + 60*60*24*30, '/');
            header('location:home.php');
            exit();
         }else{
            $error_msg[] = 'Registration failed!';
         }
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Register</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="form-container">
   <form action="" method="post">
      <h3>Create an account!</h3>
      <?php if(isset($warning_msg)){ foreach($warning_msg as $msg){ ?>
         <p class="warning-msg"><?= $msg; ?></p>
      <?php }} ?>
      <?php if(isset($error_msg)){ foreach($error_msg as $msg){ ?>
         <p class="error-msg"><?= $msg; ?></p>
      <?php }} ?>
      <input type="text" name="name" required maxlength="50" placeholder="Enter your name" class="box">
      <input type="email" name="email" required maxlength="50" placeholder="Enter your email" class="box">
      <input type="number" name="number" required min="0" max="9999999999" maxlength="10" placeholder="Enter your number" class="box">
      <input type="password" name="pass" required maxlength="20" placeholder="Enter your password" class="box">
      <input type="password" name="c_pass" required maxlength="20" placeholder="Confirm your password" class="box">
      <p>Already have an account? <a href="login.php">Login now</a></p>
      <input type="submit" value="Register Now" name="submit" class="btn">
   </form>
</section>

<?php include 'components/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src="js/script.js"></script>
<?php include 'components/message.php'; ?>

</body>
</html>