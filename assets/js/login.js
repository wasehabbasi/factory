// Show / hide password
const pwd = document.getElementById('password');
const showBtn = document.getElementById('showPassBtn');
const showIcon = document.getElementById('showIcon');

showBtn.addEventListener('click', () => {
  if (pwd.type === 'password') {
    pwd.type = 'text';
    showIcon.className = 'fa-regular fa-eye-slash';
  } else {
    pwd.type = 'password';
    showIcon.className = 'fa-regular fa-eye';
  }
});

// Ajax login
$("#loginForm").on("submit", function (e) {
  e.preventDefault();

  $.ajax({
    url: "authenticate.php",
    type: "POST",
    dataType: "json",
    data: {
      username: $("#username").val(),
      password: $("#password").val()
    },
    success: function (response) {
      if (response.status === "success") {
        $("#message").text(response.message).css("color", "green");
        setTimeout(() => window.location.href = "index.php", 1000);
      } else {
        $("#message").text(response.message).css("color", "red");
      }
    },
    error: function () {
      $("#message").text("Server error! Please try again.").css("color", "red");
    }
  });
});
