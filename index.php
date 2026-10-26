<?php
require_once "db.php";

$message = "";
$messageType = "success";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $action = $_POST["action"] ?? "";

        if ($action === "add_student") {
            $name = trim($_POST["name"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $course = trim($_POST["course"] ?? "");

            if ($name === "" || $course === "") {
                throw new Exception("Name and course are required.");
            }

            if (
                strlen($name) > 100 ||
                strlen($course) > 100 ||
                strlen($email) > 150
            ) {
                throw new Exception("One or more fields are too long.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address.");
            }

            $stmt = $pdo->prepare(
                "INSERT INTO students (name, email, course)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([$name, $email, $course]);

            $message = "Student added successfully.";

        } elseif ($action === "register") {
            $studentId = filter_var(
                $_POST["student_id"] ?? "",
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            $eventId = filter_var(
                $_POST["event_id"] ?? "",
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if (!$studentId || !$eventId) {
                throw new Exception("Please select a student and event.");
            }

            $stmt = $pdo->prepare(
                "INSERT INTO registrations (student_id, event_id)
                 VALUES (?, ?)"
            );

            $stmt->execute([$studentId, $eventId]);

            $message = "Student registered for the event successfully.";

        } else {
            throw new Exception("Invalid form submission.");
        }

    } catch (PDOException $e) {
        error_log($e->getMessage());

        if ($e->getCode() === "23000") {
            $message = "This email already exists or this student is already registered for the event.";
        } else {
            $message = "A database error occurred. Please try again.";
        }

        $messageType = "error";

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}

// Fetch students
$students = $pdo->query(
    "SELECT id, name, email, course
     FROM students
     ORDER BY id DESC"
)->fetchAll();

// Fetch events
$events = $pdo->query(
    "SELECT id, title, event_date
     FROM events
     ORDER BY event_date"
)->fetchAll();

// Fetch registrations
$registrations = $pdo->query(
    "SELECT
        s.name AS student_name,
        e.title AS event_title,
        e.event_date,
        r.registered_at
     FROM registrations r
     INNER JOIN students s ON r.student_id = s.id
     INNER JOIN events e ON r.event_id = e.id
     ORDER BY r.id DESC"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusConnect</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f2f5fa;
            margin: 0;
            padding: 25px;
            color: #263449;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        header {
            background: #243b64;
            color: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        section {
            background: white;
            padding: 22px;
            margin-bottom: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #0000000d;
        }

        h2 {
            margin-top: 0;
            color: #243b64;
        }

        label {
            display: block;
            margin-top: 12px;
        }

        input, select, button {
            width: 100%;
            padding: 11px;
            margin-top: 7px;
            border-radius: 5px;
            border: 1px solid #ccd3df;
            font-size: 15px;
        }

        button {
            background: #243b64;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 16px;
        }

        button:hover {
            background: #36588f;
        }

        .message {
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success {
            background: #e1f5e7;
            color: #176b36;
        }

        .error {
            background: #ffe8e8;
            color: #a02020;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #dce1e9;
            padding: 11px;
            text-align: left;
        }

        th {
            background: #eaf0fa;
        }

        tr:nth-child(even) {
            background: #fafbfe;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            section {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <header>
        <h1>CampusConnect</h1>
        <p>Student Management and Event Registration System</p>
    </header>

    <?php if ($message !== ""): ?>
        <div class="message <?= h($messageType) ?>">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <section>
        <h2>Add Student</h2>

        <form method="POST" action="">
            <input type="hidden" name="action" value="add_student">

            <label for="name">Student Name</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                required
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                required
            >

            <label for="course">Course</label>
            <input
                type="text"
                id="course"
                name="course"
                maxlength="100"
                required
            >

            <button type="submit">Add Student</button>
        </form>
    </section>

    <section>
        <h2>Register for Event</h2>

        <form method="POST" action="">
            <input type="hidden" name="action" value="register">

            <label for="student_id">Select Student</label>
            <select id="student_id" name="student_id" required>
                <option value="">-- Select Student --</option>

                <?php foreach ($students as $student): ?>
                    <option value="<?= h($student["id"]) ?>">
                        <?= h($student["name"]) ?>
                        (<?= h($student["email"]) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="event_id">Select Event</label>
            <select id="event_id" name="event_id" required>
                <option value="">-- Select Event --</option>

                <?php foreach ($events as $event): ?>
                    <option value="<?= h($event["id"]) ?>">
                        <?= h($event["title"]) ?>
                        (<?= h($event["event_date"]) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Register Student</button>
        </form>
    </section>

    <section>
        <h2>Student Records</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="4">No student records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= h($student["id"]) ?></td>
                            <td><?= h($student["name"]) ?></td>
                            <td><?= h($student["email"]) ?></td>
                            <td><?= h($student["course"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2>Event Registrations</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Event</th>
                        <th>Event Date</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($registrations)): ?>
                    <tr>
                        <td colspan="4">No registrations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registrations as $registration): ?>
                        <tr>
                            <td><?= h($registration["student_name"]) ?></td>
                            <td><?= h($registration["event_title"]) ?></td>
                            <td><?= h($registration["event_date"]) ?></td>
                            <td><?= h($registration["registered_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>
</body>
</html>