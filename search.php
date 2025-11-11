<?php
session_start();
require_once 'php/db_connect.php';
require_once 'php/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// ---- Handle AJAX search requests ----
if (isset($_GET['q'])) {
    $search = $_GET['q'];

    $stmt = $conn->prepare("SELECT First_Name FROM users WHERE First_Name LIKE ? LIMIT 5");
    $term = "%" . $search . "%";
    $stmt->bind_param("s", $term);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<p>" . htmlspecialchars($row['First_Name']) . "</p>";
        }
    } else {
        echo "<p>No results found</p>";
    }

    $stmt->close();
    exit; // prevent the rest of the HTML from being sent
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Search People</title>
  <style>
    input {
      width: 250px;
      padding: 8px;
    }
    #results {
      margin-top: 10px;
      border: 1px solid #ccc;
      max-width: 250px;
      background: #fff;
      position: absolute;
      z-index: 100;
    }
    #results p {
      margin: 0;
      padding: 8px;
      cursor: pointer;
    }
    #results p:hover {
      background: #f0f0f0;
    }
  </style>
</head>
<body>
  <h2>Search for a Person</h2>
  <input type="text" id="search" placeholder="Type a name...">
  <div id="results"></div>

  <script>
    const search = document.getElementById("search");
    const results = document.getElementById("results");

    search.addEventListener("keyup", () => {
      const query = search.value.trim();

      if (query.length === 0) {
        results.innerHTML = "";
        return;
      }

      // AJAX request to THIS SAME FILE
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "search.php?q=" + encodeURIComponent(query), true);
      xhr.onload = function() {
        if (this.status === 200) {
          results.innerHTML = this.responseText;
        }
      };
      xhr.send();
    });

    // Optional: fill search box when a result is clicked
    results.addEventListener("click", e => {
      if (e.target.tagName === "P") {
        search.value = e.target.textContent;
        results.innerHTML = "";
      }
    });
  </script>
</body>
</html>
