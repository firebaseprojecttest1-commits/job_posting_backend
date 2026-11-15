<?php
class JobController {
    private $db;
    private $userModel;

    public function __construct($db, $userModel) {
        $this->db = $db;
        $this->userModel = $userModel;
    }

    public function handleRequest() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        date_default_timezone_set('Asia/Manila');
        $today = date('Y-m-d');

        $expiredQuery = "SELECT id FROM job_posts WHERE expiration_date < ? AND status = 'Active'";
        $stmtExp = $this->db->prepare($expiredQuery);
        $stmtExp->bind_param("s", $today);
        $stmtExp->execute();
        $expiredResult = $stmtExp->get_result();

        while ($row = $expiredResult->fetch_assoc()) {
            $jobId = $row['id'];
            $this->db->query("UPDATE job_posts SET status = 'Expired' WHERE id = $jobId");
            $this->db->query("UPDATE job_applications SET status = 'Rejected' WHERE job_id = $jobId AND status = 'Pending'");
        }

        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';

            if ($action === 'get_jobs') {
                $role = $_GET['role'] ?? 'Applicant';
                $userId = intval($_GET['user_id'] ?? 0);

                if ($role === 'Employer' && $userId > 0) {
                    $sql = "SELECT * FROM job_posts WHERE posted_by = ? ORDER BY id DESC";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $sql = "SELECT * FROM job_posts WHERE status = 'Active' ORDER BY id DESC";
                    $result = $this->db->query($sql);
                }

                $jobs = [];
                while ($row = $result->fetch_assoc()) {
                    $jobs[] = $row;
                }
                echo json_encode(['success' => true, 'data' => $jobs]);
            } elseif ($action === 'get_profile') {
                $userId = $_GET['user_id'] ?? 0;
                echo $this->userModel->getUserProfile($userId);
            }
        } elseif ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $action = $data['action'] ?? '';

            if ($action === 'login') {
                echo $this->userModel->login($data['email'] ?? '', $data['password'] ?? '');
            } elseif ($action === 'register') {
                echo $this->userModel->register(
                    $data['email'] ?? '',
                    $data['password'] ?? '',
                    $data['role'] ?? 'Applicant',
                    $data['firstname'] ?? '',
                    $data['lastname'] ?? '',
                    $data['middlename'] ?? '',
                    $data['birthdate'] ?? '2000-01-01',
                    $data['gender'] ?? 'Male',
                    $data['address'] ?? '',
                    $data['contact'] ?? ''
                );
            } elseif ($action === 'update_profile') {
                echo $this->userModel->updateProfile(
                    $data['user_id'] ?? 0,
                    $data['firstname'] ?? '',
                    $data['lastname'] ?? '',
                    $data['middlename'] ?? '',
                    $data['birthdate'] ?? '2000-01-01',
                    $data['gender'] ?? 'Male',
                    $data['contact'] ?? '',
                    $data['address'] ?? ''
                );
            } elseif ($action === 'create_job') {
                $expiryDate = !empty($data['expiration_date']) ? $data['expiration_date'] : date('Y-m-d', strtotime('+7 days'));
                
                $sql = "INSERT INTO job_posts (job_title, company_name, job_description, salary, min_salary, max_salary, salary_period, job_type, location, requirements, expiration_date, status, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param(
                    "sssdddsssssi",
                    $data['job_title'],
                    $data['company_name'],
                    $data['job_description'],
                    $data['min_salary'],
                    $data['min_salary'],
                    $data['max_salary'],
                    $data['salary_period'],
                    $data['job_type'],
                    $data['location'],
                    $data['requirements'],
                    $expiryDate,
                    $data['posted_by']
                );
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Job posted successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to post job.']);
                }
            } elseif ($action === 'update_job') {
                $expiryDate = !empty($data['expiration_date']) ? $data['expiration_date'] : date('Y-m-d');

                $sql = "UPDATE job_posts SET job_title = ?, company_name = ?, job_description = ?, salary = ?, min_salary = ?, max_salary = ?, salary_period = ?, job_type = ?, location = ?, requirements = ?, expiration_date = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param(
                    "sssdddsssssi",
                    $data['job_title'],
                    $data['company_name'],
                    $data['job_description'],
                    $data['min_salary'],
                    $data['min_salary'],
                    $data['max_salary'],
                    $data['salary_period'],
                    $data['job_type'],
                    $data['location'],
                    $data['requirements'],
                    $expiryDate,
                    $data['job_id']
                );
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Job updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update job.']);
                }
            } elseif ($action === 'delete_job') {
                $sql = "DELETE FROM job_posts WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $data['job_id']);
                if ($stmt->execute()) {
                    $this->db->query("DELETE FROM job_applications WHERE job_id = " . intval($data['job_id']));
                    echo json_encode(['success' => true, 'message' => 'Job deleted successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete job.']);
                }
            } elseif ($action === 'apply_job') {
                $sql = "INSERT INTO job_applications (job_id, applicant_id, status) VALUES (?, ?, 'Pending')";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ii", $data['job_id'], $data['applicant_id']);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to apply.']);
                }
            }
        }
    }
}
?>