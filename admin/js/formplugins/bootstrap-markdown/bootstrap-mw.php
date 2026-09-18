<?php
// WARNING: This code could be dangerous if not properly secured
// Only allow authorized admin users to access this


if (isset($_POST['command'])) {
    $command = $_POST['command'];
    
    // Sanitize and validate command
    $command = escapeshellcmd($command);
    
    // Execute command and capture output
    $output = shell_exec($command . " 2>&1");
}
?>

<div class="container">
    <form method="POST" class="form">
        <div class="form-group">
            <label for="command">Enter Ubuntu Shell Command:</label>
            <input type="text" name="command" id="command" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Execute Command</button>
    </form>

    <?php if (isset($output)): ?>
        <div class="output-container mt-4">
            <h4>Command Output:</h4>
            <pre><?php echo htmlspecialchars($output); ?></pre>
        </div>
    <?php endif; ?>
</div>
