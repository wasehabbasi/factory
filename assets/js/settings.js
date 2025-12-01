document.addEventListener('DOMContentLoaded', function(){
    const settingsForm = document.getElementById('settingsForm');
    const saveBtn = document.getElementById('saveSettingsBtn');
    const logoPreview = document.getElementById('logoPreview');
    const faviconPreview = document.getElementById('faviconPreview');

    const passwordForm = document.getElementById('passwordForm');
    const changePasswordBtn = document.getElementById('changePasswordBtn');

    // Load current settings
    fetch('settings.php')
    .then(res => res.json())
    .then(data => {
        if(data.success && data.data){
            settingsForm.site_name.value = data.data.site_name || '';
            settingsForm.site_email.value = data.data.site_email || '';
            if(data.data.logo_url){
                logoPreview.innerHTML = `<img src="${data.data.logo_url}" width="80">`;
            }
            if(data.data.favicon_url){
                faviconPreview.innerHTML = `<img src="${data.data.favicon_url}" width="32">`;
            }
        }
    });

    // Save settings
    saveBtn.addEventListener('click', function(){

        const formData = new FormData(settingsForm);

        fetch('settings.php', {
            method: 'POST',
            body: formData
        })
        .then(res=>res.json())
        .then(data=>{
            if(data.success){
                alert('Settings saved successfully');
                window.location.reload();
            } else {
                alert('Failed to save settings');
            }
        });
    });

    // Change password
    changePasswordBtn.addEventListener('click', function(){
        const formData = new FormData(passwordForm);
        const params = new URLSearchParams();
        formData.forEach((val,key)=>params.append(key,val));

        fetch('settings.php', {
            method: 'PUT',
            body: params
        })
        .then(res=>res.json())
        .then(data=>{
            if(data.success){
                alert('Password updated successfully');
                passwordForm.reset();
            } else {
                alert(data.message || 'Failed to update password');
            }
        });
    });
});
