<?php

$db = "database.json";
$loginStatus = false;

$usr = null;
$pass = null;

login:
while (!$loginStatus) {
    echo "_____LOGIN_____" . PHP_EOL;
    echo "username : ";
    $usr = trim(fgets(STDIN));

    echo "password : ";
    $pass = trim(fgets(STDIN));

    $loginStatus = login($db, $usr, $pass);
}

while ($loginStatus) {
    echo "______MENU______" . PHP_EOL;
    echo "1. View task" . PHP_EOL;
    echo "2. Create task" . PHP_EOL;
    echo "3. Edit task" . PHP_EOL;
    echo "4. Delete task" . PHP_EOL;
    echo "5. Log out" . PHP_EOL;
    echo "Input : ";

    $usrInput = trim(fgets(STDIN));
    echo PHP_EOL;

    if (is_numeric(($usrInput)) && $usrInput > 0 && $usrInput < 6) {
        switch ($usrInput) {
            case 1:
                viewTask($db, $usr);
                break;
            case 2:
                createTask($db, $usr);
                break;
            case 3:
                editTask($db, $usr);
                break;
            case 4:
                deleteTask($db, $usr);
            case 5:
                $loginStatus = false;
                break;
        }
    }
}

goto login;

function login($dbpath, $username, $password)
{
    $users = readDB($dbpath);

    foreach ($users as $user) {
        if ($user["username"] === $username) {
            if ($user["password"] === $password) {
                echo "Berhasil Login!" . PHP_EOL . PHP_EOL;
                return true;
            } else {
                echo "username atau password salah" . PHP_EOL;
                return false;
            }
        }
    }
    echo "user {$username} tidak ditemukan!" . PHP_EOL . PHP_EOL;
    return false;
}

function readDB($dbpath)
{
    if (!file_exists($dbpath)) {
        return [];
    }
    $data = file_get_contents($dbpath);
    return json_decode($data, true) ?? [];
}

function viewTask($dbpath, $usr)
{
    $users = readDB($dbpath);

    foreach ($users as &$user) {
        if ($user["username"] === $usr) {
            echo "___DAFTAR TASK___" . PHP_EOL;

            if (isset($user["tasks"]) && !empty($user["tasks"])) {
                foreach ($user["tasks"] as $index => $task) {
                    echo "Task #" . ($index + 1) . PHP_EOL;
                    echo "Name     : " . $task["task_name"] . PHP_EOL;
                    echo "Due Date : " . $task["due_date"] . PHP_EOL;
                    echo "Status   : " . $task["status"] . PHP_EOL . PHP_EOL;
                }
            } else {
                echo "Tidak ada tugas untuk user ini." . PHP_EOL;
            }
            return;
        }
    }
}

function createTask($dbpath, $usr)
{
    $users = readDB($dbpath);

    foreach ($users as &$user) {
        if ($user["username"] === $usr) {
            echo "Task name : ";
            $nameTask = trim(fgets(STDIN));
            echo "Due date  : ";
            $dueDate = trim(fgets(STDIN));
            $newTask = [
                "task_name" => $nameTask,
                "due_date" => $dueDate,
                "status" => "todo"
            ];

            if (!isset($user["tasks"])) {
                $user["tasks"] = [];
            }
            $user["tasks"][] = $newTask;

            if (file_put_contents($dbpath, json_encode($users, JSON_PRETTY_PRINT))) {
                echo "Task baru berhasil ditambahkan!" . PHP_EOL;
            } else {
                echo "Gagal menambahkan task baru." . PHP_EOL;
            }
            return;
        }
    }
}

function editTask($dbpath, $usr)
{
    $users = readDB($dbpath);

    // Cari pengguna
    $userIndex = findUserIndex($users, $usr);
    if ($userIndex === null) {
        echo "Pengguna {$usr} tidak ditemukan!" . PHP_EOL;
        return;
    }

    $taskIndex = searchTask($users[$userIndex]);
    if ($taskIndex === null) {
        echo "Tugas tidak ditemukan!" . PHP_EOL;
        return;
    }

    if (!editTaskDetails($users[$userIndex]["tasks"][$taskIndex])) {
        echo "Perubahan tugas dibatalkan." . PHP_EOL;
        return;
    }

    if (!file_put_contents($dbpath, json_encode($users, JSON_PRETTY_PRINT))) {
        echo "Gagal menyimpan perubahan." . PHP_EOL;
        return;
    }

    echo "Tugas berhasil diperbarui!" . PHP_EOL;
}

function findUserIndex($users, $usr)
{
    foreach ($users as $index => $user) {
        if ($user["username"] === $usr) {
            return $index;
        }
    }
    return null;
}

function searchTask($user)
{
    echo "Search task: ";
    $search = trim(fgets(STDIN));

    if (empty($user["tasks"])) {
        echo "Pengguna tidak memiliki tugas" . PHP_EOL;
        return null;
    }

    foreach ($user["tasks"] as $index => $task) {
        if (strtolower($task["task_name"]) === strtolower($search)) {
            echo "Tugas ditemukan!" . PHP_EOL;
            echo "Name     : " . $task["task_name"] . PHP_EOL;
            echo "Due Date : " . $task["due_date"] . PHP_EOL;
            echo "Status   : " . $task["status"] . PHP_EOL . PHP_EOL;

            return $index; // Mengembalikan index tugas yang ditemukan
        }
    }

    echo "Tugas tidak ditemukan!" . PHP_EOL;
    return null; // Jika tugas tidak ditemukan
}

function editTaskDetails(&$task)
{
    echo "Edit task name (leave empty to keep the same): ";
    $newTaskName = trim(fgets(STDIN));
    if ($newTaskName) {
        $task["task_name"] = $newTaskName;
    }

    echo "Edit due date (leave empty to keep the same): ";
    $newDueDate = trim(fgets(STDIN));
    if ($newDueDate) {
        $task["due_date"] = $newDueDate;
    }

    echo "Edit status (leave empty to keep the same): ";
    $newStatus = trim(fgets(STDIN));
    if ($newStatus) {
        $task["status"] = $newStatus;
    }

    echo "Perubahan berhasil diterapkan." . PHP_EOL;
    return true; // Menandakan tugas berhasil diperbarui
}

function deleteTask($dbpath, $usr){
    $users = readDB($dbpath);

    $userIndex = findUserIndex($users, $usr);

    $taskIndex = searchTask($users[$userIndex]);
    if ($taskIndex === null) {
        echo "Tugas tidak ditemukan!" . PHP_EOL;
        return;
    }

    unset($users[$userIndex]["tasks"][$taskIndex]); // menghapus suatu task dari user

    $users[$userIndex]["tasks"] = array_values($users[$userIndex]["tasks"]);

    if (file_put_contents($dbpath, json_encode($users, JSON_PRETTY_PRINT))) {
        echo "Tugas berhasil dihapus!" . PHP_EOL;
    } else {
        echo "Gagal menyimpan perubahan." . PHP_EOL;
    }
}
