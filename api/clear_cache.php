<?php
// Force PHP opcode cache reset
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OpCache reset successful<br>";
} else {
    echo "ℹ️ OpCache not available<br>";
}

// Clear APCu cache if available
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "✅ APCu cache cleared<br>";
}

echo "<p>Cache cleared. Please <a href='../'>go back</a> and test reports again.</p>";
