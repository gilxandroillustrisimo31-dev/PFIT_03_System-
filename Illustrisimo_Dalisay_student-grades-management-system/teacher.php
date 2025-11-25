<?php
include 'includes/conn.php';
include 'includes/functions.php';
requireRole('teacher');

$teacher_id = $_SESSION['user_id'];

// Get teacher's classrooms
$classrooms = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ?");
$classrooms->execute([$teacher_id]);
$teacher_classrooms = $classrooms->fetchAll();

// Get students in teacher's classrooms
$students_stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.username, c.name as classroom_name 
    FROM users u 
    JOIN classroom_students cs ON u.id = cs.student_id 
    JOIN classrooms c ON cs.classroom_id = c.id 
    WHERE c.teacher_id = ? 
    ORDER BY c.name, u.first_name
");
$students_stmt->execute([$teacher_id]);
$students = $students_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Student Grade Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            min-height: 100vh;
            padding: 0;
        }
        .sidebar .nav-link {
            color: white;
            padding: 1rem 1.5rem;
            border-left: 4px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-left-color: white;
        }
        .grade-card {
            transition: transform 0.2s;
        }
        .grade-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="d-flex flex-column p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                        <h5>Teacher Portal</h5>
                        <small><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></small>
                    </div>
                    
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#dashboard" data-bs-toggle="tab">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#students" data-bs-toggle="tab">
                                <i class="fas fa-user-graduate me-2"></i>My Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="grades.php">
                                <i class="fas fa-edit me-2"></i>Manage Grades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="classroom.php">
                                <i class="fas fa-chalkboard me-2"></i>My Classrooms
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade show active" id="dashboard">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Teacher Dashboard</h2>
                            
                        </div>

                        <!-- Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card grade-card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4><?php echo count($teacher_classrooms); ?></h4>
                                                <p>My Classrooms</p>
                                            </div>
                                            <i class="fas fa-chalkboard fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card grade-card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4><?php echo count($students); ?></h4>
                                                <p>Total Students</p>
                                            </div>
                                            <i class="fas fa-user-graduate fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card grade-card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4>0</h4>
                                                <p>Grades Assigned</p>
                                            </div>
                                            <i class="fas fa-edit fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- My Classrooms -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>My Classrooms</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php foreach($teacher_classrooms as $classroom): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card grade-card border-primary">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?php echo $classroom['name']; ?></h5>
                                                        <p class="card-text"><?php echo $classroom['description']; ?></p>
                                                        <a href="classroom.php?id=<?php echo $classroom['id']; ?>" class="btn btn-primary btn-sm">Manage</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Students Tab -->
                    <div class="tab-pane fade" id="students">
                        <h2 class="mb-4">My Students</h2>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5>Student List</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Classroom</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($students as $student): ?>
                                            <tr>
                                                <td><?php echo $student['id']; ?></td>
                                                <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                                <td><?php echo $student['username']; ?></td>
                                                <td><?php echo $student['classroom_name']; ?></td>
                                                <td>
                                                    <a href="grades.php?student_id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">View Grades</a>
                                                    <a href="grades.php?action=add&student_id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-success">Add Grade</a>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'))
            triggerTabList.forEach(function (triggerEl) {
                var tabTrigger = new bootstrap.Tab(triggerEl)
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault()
                    tabTrigger.show()
                })
            })
        });
    </script>
</body>
</html>