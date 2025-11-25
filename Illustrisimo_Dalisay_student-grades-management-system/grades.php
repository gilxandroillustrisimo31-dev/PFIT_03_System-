<?php
include 'includes/conn.php';
include 'includes/functions.php';

if (!isLoggedIn() || !hasRole('teacher')) {
    redirect('index.php');
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grade'])) {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $classroom_id = $_POST['classroom_id'];
    $grade = $_POST['grade'];
    $grade_type = $_POST['grade_type'];
    $remarks = $_POST['remarks'];
    
    $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject_id, classroom_id, teacher_id, grade, grade_type, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$student_id, $subject_id, $classroom_id, $teacher_id, $grade, $grade_type, $remarks]);
    $message = "Grade added successfully!";
    $message_type = "success";
}

// Handle grade deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_grade' && isset($_GET['id'])) {
    $grade_id = $_GET['id'];
    
    $result = deleteGrade($pdo, $grade_id, $teacher_id);
    if ($result === true) {
        $message = "Grade deleted successfully!";
        $message_type = "success";
    } else {
        $message = $result;
        $message_type = "danger";
    }
}

// Get teacher's classrooms and students
$classrooms = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ?");
$classrooms->execute([$teacher_id]);
$teacher_classrooms = $classrooms->fetchAll();

// Get subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();

// Get students in teacher's classrooms
$students_stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name 
    FROM users u 
    JOIN classroom_students cs ON u.id = cs.student_id 
    JOIN classrooms c ON cs.classroom_id = c.id 
    WHERE c.teacher_id = ? 
    ORDER BY u.first_name
");
$students_stmt->execute([$teacher_id]);
$students = $students_stmt->fetchAll();

// Get grades for viewing
$grades_stmt = $pdo->prepare("
    SELECT g.*, u.first_name, u.last_name, s.name as subject_name, c.name as classroom_name 
    FROM grades g 
    JOIN users u ON g.student_id = u.id 
    JOIN subjects s ON g.subject_id = s.id 
    JOIN classrooms c ON g.classroom_id = c.id 
    WHERE g.teacher_id = ? 
    ORDER BY g.graded_at DESC
");
$grades_stmt->execute([$teacher_id]);
$grades = $grades_stmt->fetchAll();

// Calculate statistics
$total_grades = count($grades);
$average_grade = 0;
if ($total_grades > 0) {
    $sum = 0;
    foreach ($grades as $grade) {
        $sum += $grade['grade'];
    }
    $average_grade = $sum / $total_grades;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades - Student Grade Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .grade-badge {
            font-size: 0.9em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container">
            <a class="navbar-brand" href="teacher.php">
                <i class="fas fa-edit"></i> Grade Management
            </a>
            <div class="navbar-nav ms-auto">
               
                <a class="nav-link" href="teacher.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-plus-circle"></i> Add New Grade</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Student</label>
                                <select class="form-select" name="student_id" required>
                                    <option value="">Select student...</option>
                                    <?php foreach($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Subject</label>
                                        <select class="form-select" name="subject_id" required>
                                            <option value="">Select subject...</option>
                                            <?php foreach($subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>"><?php echo $subject['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Classroom</label>
                                        <select class="form-select" name="classroom_id" required>
                                            <option value="">Select classroom...</option>
                                            <?php foreach($teacher_classrooms as $classroom): ?>
                                            <option value="<?php echo $classroom['id']; ?>"><?php echo $classroom['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Grade</label>
                                        <input type="number" class="form-control" name="grade" min="0" max="100" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Grade Type</label>
                                        <select class="form-select" name="grade_type" required>
                                            <option value="">Select type...</option>
                                            <option value="quiz">Quiz</option>
                                            <option value="assignment">Assignment</option>
                                            <option value="exam">Exam</option>
                                            <option value="project">Project</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" name="remarks" rows="2"></textarea>
                            </div>
                            <button type="submit" name="add_grade" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Grade
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-chart-bar"></i> Grade Statistics</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border rounded p-3">
                                    <h3 class="text-primary"><?php echo $total_grades; ?></h3>
                                    <small>Total Grades</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3">
                                    <h3 class="text-success"><?php echo number_format($average_grade, 1); ?></h3>
                                    <small>Average Grade</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3">
                                    <h3 class="text-info"><?php echo count($teacher_classrooms); ?></h3>
                                    <small>Classrooms</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h4><i class="fas fa-info-circle"></i> Grading Scale</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><td>90-100</td><td><span class="badge bg-success">A</span></td><td>Excellent</td></tr>
                            <tr><td>80-89</td><td><span class="badge bg-info">B</span></td><td>Good</td></tr>
                            <tr><td>70-79</td><td><span class="badge bg-warning">C</span></td><td>Satisfactory</td></tr>
                            <tr><td>60-69</td><td><span class="badge bg-danger">D</span></td><td>Needs Improvement</td></tr>
                            <tr><td>Below 60</td><td><span class="badge bg-dark">F</span></td><td>Fail</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grades Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-list"></i> Recent Grades</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Classroom</th>
                                        <th>Grade</th>
                                        <th>Type</th>
                                        <th>Remarks</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($grades as $grade): 
                                        $grade_point = calculateGradePoint($grade['grade']);
                                        $grade_color = getGradeColor($grade['grade']);
                                    ?>
                                    <tr>
                                        <td><?php echo $grade['first_name'] . ' ' . $grade['last_name']; ?></td>
                                        <td><?php echo $grade['subject_name']; ?></td>
                                        <td><?php echo $grade['classroom_name']; ?></td>
                                        <td>
                                            <span class="badge grade-badge bg-<?php echo $grade_color; ?>">
                                                <?php echo $grade['grade']; ?> (<?php echo $grade_point; ?>)
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo ucfirst($grade['grade_type']); ?></span>
                                        </td>
                                        <td><?php echo $grade['remarks']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($grade['graded_at'])); ?></td>
                                        <td>
                                            <a href="?action=delete_grade&id=<?php echo $grade['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this grade?')">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>