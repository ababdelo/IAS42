<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <title>CSLM42 - Error</title>
   <link rel="shortcut icon" href="/assets/imgs/logo/logo.ico" type="image/x-icon">
   <link rel="stylesheet" href="/assets/css/errors.css" />
   <link rel="stylesheet" href="/assets/css/fontAwesome.css">
</head>

<body>
   <div class="container">
      
      <a href="/" class="back-link">
         <i class="fa-solid fa-arrow-left"></i>
         Back to <span class="tag">home</span> page
      </a>

      <div class="wrap">
         <img src="/assets/imgs/svg/ghost.svg" class="ghost" alt="Floating ghost">
         <p class="shadowFrame">
            <img src="/assets/imgs/svg/shadow.svg" alt="Shadow frame" class="shadow">
         </p>
      </div>
      
      <div class="ttl"> 
         <span class="code-error"></span> 
         <span class="error-title"></span> 
      </div>
      
      <span id="source" class="error-description"></span>
   </div>
   
   <!-- Silently grabs the error code from Apache so the URL doesn't have to change -->
   <script>
      const SERVER_ERROR_CODE = "<?php echo isset($_SERVER['REDIRECT_STATUS']) ? $_SERVER['REDIRECT_STATUS'] : '404'; ?>";
   </script>
   
   <script src="/assets/js/errors.js"></script>
</body>

</html>
