<?php declare(strict_types=1); ?>
<html>
<head>
    <title>Base de Connaissances</title>
    <link rel="stylesheet" href="styles/login.css">
</head>
<body>
    <?php if($secure): ?>
        bla bla bla login
    <?php else: ?>
        <select>
            <option value="">--Veuillez choisir un profil--</option>
        <?php foreach($users as $user): ?>
            <option value="<?= $user['user_id'] ?>"><?= $user['user_name'] ?></option>
        <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
<script>
const select = document.querySelector('select');
select.addEventListener('change', () => {
    const user_id = select.value;
    fetch("/login", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ user_id })
    })
    .then(response => {
        if (response.ok) {
            location.href = "/";
        } else {
            return response.text().then(text => { throw new Error(text) });
        }
    })
    .catch(error => {
        alert("Login failed: " + error.message);
    });
});
</script>
</body>
</html>