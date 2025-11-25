<?php
include 'includes/conn.php';
include 'includes/functions.php';

if (!isLoggedIn() || !hasRole('teacher')) {
    redirect('index.php');
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle classroom creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_classroom'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    $stmt = $pdo->prepare("INSERT INTO classrooms (name, description, teacher_id) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $teacher_id]);
    $message = "Classroom created successfully!";
    $message_type = "success";
}

// Handle student addition to classroom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $classroom_id = $_POST['classroom_id'];
    $student_id = $_POST['student_id'];
    
    // Check if student is already in classroom
    $check_stmt = $pdo->prepare("SELECT * FROM classroom_students WHERE classroom_id = ? AND student_id = ?");
    $check_stmt->execute([$classroom_id, $student_id]);
    
    if ($check_stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO classroom_students (classroom_id, student_id) VALUES (?, ?)");
        $stmt->execute([$classroom_id, $student_id]);
        $message = "Student added to classroom successfully!";
        $message_type = "success";
    } else {
        $message = "Student is already in this classroom!";
        $message_type = "danger";
    }
}

// Handle student removal from classroom
if (isset($_GET['action']) && $_GET['action'] === 'remove_student' && isset($_GET['classroom_id']) && isset($_GET['student_id'])) {
    $classroom_id = $_GET['classroom_id'];
    $student_id = $_GET['student_id'];
    
    $result = removeStudentFromClassroom($pdo, $classroom_id, $student_id);
    if ($result === true) {
        $message = "Student removed from classroom successfully!";
        $message_type = "success";
    } else {
        $message = $result;
        $message_type = "danger";
    }
}

// Handle classroom deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_classroom' && isset($_GET['id'])) {
    $classroom_id = $_GET['id'];
    
    // Verify the classroom belongs to the teacher
    $check_stmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ? AND teacher_id = ?");
    $check_stmt->execute([$classroom_id, $teacher_id]);
    
    if ($check_stmt->rowCount() > 0) {
        $result = deleteClassroom($pdo, $classroom_id);
        if ($result === true) {
            $message = "Classroom deleted successfully!";
            $message_type = "success";
        } else {
            $message = $result;
            $message_type = "danger";
        }
    } else {
        $message = "Classroom not found or you don't have permission to delete it!";
        $message_type = "danger";
    }
}

// Get teacher's classrooms
$classrooms = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ?");
$classrooms->execute([$teacher_id]);
$teacher_classrooms = $classrooms->fetchAll();

// Get all students
$students = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY first_name, last_name")->fetchAll();

// Get students for a specific classroom if requested
$classroom_students = [];
if (isset($_GET['view_students']) && isset($_GET['classroom_id'])) {
    $classroom_id = $_GET['classroom_id'];
    $classroom_students = getClassroomStudents($pdo, $classroom_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classrooms - Student Grade Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="teacher.php">
                <i class="fas fa-chalkboard-teacher"></i> Teacher Portal
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

        <?php if (isset($_GET['view_students']) && isset($_GET['classroom_id'])): ?>
            <!-- View Students in Classroom -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>
                        <i class="fas fa-users"></i> 
                        Students in <?php echo $teacher_classrooms[array_search($_GET['classroom_id'], array_column($teacher_classrooms, 'id'))]['name']; ?>
                    </h4>
                    <a href="classroom.php" class="btn btn-secondary">Back to Classrooms</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($classroom_students as $student): ?>
                                <tr>
                                    <td><?php echo $student['id']; ?></td>
                                    <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                    <td><?php echo $student['username']; ?></td>
                                    <td><?php echo $student['email']; ?></td>
                                    <td>
                                        <a href="?action=remove_student&classroom_id=<?php echo $_GET['classroom_id']; ?>&student_id=<?php echo $student['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to remove this student from the classroom?')">
                                            Remove
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Main Classroom Management -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-plus-circle"></i> Create New Classroom</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Classroom Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>
                                <button type="submit" name="create_classroom" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Classroom
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-user-plus"></i> Add Student to Classroom</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Select Classroom</label>
                                    <select class="form-select" name="classroom_id" required>
                                        <option value="">Choose classroom...</option>
                                        <?php foreach($teacher_classrooms as $classroom): ?>
                                        <option value="<?php echo $classroom['id']; ?>"><?php echo $classroom['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select Student</label>
                                    <select class="form-select" name="student_id" required>
                                        <option value="">Choose student...</option>
                                        <?php foreach($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_student" class="btn btn-success">
                                    <i class="fas fa-user-plus"></i> Add Student
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Existing Classrooms -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-chalkboard"></i> My Classrooms</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Students</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($teacher_classrooms as $classroom): 
                                            // Count students in this classroom
                                            $student_count = $pdo->prepare("SELECT COUNT(*) FROM classroom_students WHERE classroom_id = ?");
                                            $student_count->execute([$classroom['id']]);
                                            $count = $student_count->fetchColumn();
                                        ?>
                                        <tr>
                                            <td><?php echo $classroom['name']; ?></td>
                                            <td><?php echo $classroom['description']; ?></td>
                                            <td><span class="badge bg-primary"><?php echo $count; ?> students</span></td>
                                            <td>
                                                <a href="?view_students=true&classroom_id=<?php echo $classroom['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info">
                                                    View Students
                                                </a>
                                                <a href="?action=delete_classroom&id=<?php echo $classroom['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this classroom? This will also delete all grades associated with it.')">
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
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>