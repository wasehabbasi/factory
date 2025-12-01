document.addEventListener('DOMContentLoaded', function(){
    const profileView = document.getElementById('profileView');
    const profileForm = document.getElementById('profileForm');
    const saveBtn = document.getElementById('saveProfileBtn');

    // Load profile
    fetch('profile.php')
    .then(res=>res.json())
    .then(data=>{
        if(data.success && data.data){
            const u = data.data;
            profileView.innerHTML = `
                <p><strong>Name:</strong> ${u.name}</p>
                <p><strong>Email:</strong> ${u.email}</p>
                <p><strong>Role:</strong> ${u.role_name}</p>
                <p><strong>Status:</strong> ${u.status}</p>
                <p><strong>Last Login:</strong> ${u.last_login || '-'}</p>
            `;

            // form prefill
            profileForm.name.value = u.name;
            profileForm.email.value = u.email;
            profileForm.role_name.value = u.role_name;
        }
    });

    // Save changes
    saveBtn.addEventListener('click', function(){
        const formData = new FormData(profileForm);

        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
        .then(res=>res.json())
        .then(data=>{
            if(data.success){
                alert("Profile updated successfully");
                window.location.reload();
            } else {
                alert(data.message || "Failed to update profile");
            }
        });
    });
});
