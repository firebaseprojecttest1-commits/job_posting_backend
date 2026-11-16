<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Posting</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { margin-bottom: 20px; color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; border: none; color: white; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        #result { margin-top: 15px; padding: 10px; border-radius: 4px; white-space: pre-wrap; font-family: monospace; font-size: 14px; word-break: break-all; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Login Test</h2>
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="test10@gmail.com">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required value="test10">
            </div>
            <button type="submit">Login</button>
        </form>
        <div id="result"></div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const resultDiv = document.getElementById('result');

            resultDiv.className = '';
            resultDiv.textContent = 'Logging in...';

            try {
                const response = await fetch('Class/User.php?op=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    throw new Error('Invalid JSON response: ' + text);
                }

                resultDiv.textContent = JSON.stringify(data, null, 2);
                
                if (data.success) {
                    resultDiv.className = 'success';
                } else {
                    resultDiv.className = 'error';
                }
            } catch (err) {
                resultDiv.textContent = 'Error: ' + err.message;
                resultDiv.className = 'error';
            }
        });
    </script>
</body>
</html>