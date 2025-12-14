function openModal(id) {
    document.getElementById(id).style.display = "block";
}

function closeModal(id) {
    const modal = document.getElementById(id);
    modal.style.display = "none";

    const form = modal.querySelector("form");
    if (form) form.reset();
}

window.addEventListener("click", function (event) {
    document.querySelectorAll(".modal").forEach((modal) => {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });
});

function openUserModal(role) {
    const modal = document.getElementById("userModal");
    modal.style.display = "block";

    document.querySelector("input[name='password']").required = true;
    document.querySelector("input[name='confirm_password']").required = true;

    const roleHidden = document.getElementById("roleHidden");
    const roleTitle = document.getElementById("modalRoleTitle");

    const specField = document.getElementById("specializationField");
    const specInput = document.getElementById("specializationInput");

    roleHidden.value = role;
    roleTitle.innerText = role;

    document.getElementById("modalRoleTitle").innerText = "Create " + role;

    if (role === "Doctor") {
        specField.style.display = "flex";
        specInput.required = true;
    } else {
        specField.style.display = "none";
        specInput.required = false;
        specInput.value = "";
    }
}

function openEditUserModal(user) {
    const modal = document.getElementById("userModal");
    modal.style.display = "block";

    document.getElementById("formMode").value = "edit";
    document.getElementById("editUserId").value = user.user_id;

    document.querySelector("input[name='full_name']").value = user.full_name;
    document.querySelector("input[name='username']").value = user.username;
    document.querySelector("input[name='email']").value = user.email;

    document.getElementById("roleHidden").value = user.role;
    document.getElementById("modalRoleTitle").innerText = "Edit " + user.role;

    // Password optional
    document.querySelector("input[name='password']").required = false;
    document.querySelector("input[name='confirm_password']").required = false;

    const submitBtn = document.getElementById("submitBtn");
    submitBtn.innerText = "Update";
    submitBtn.name = "update_user";

    const specField = document.getElementById("specializationField");
    const specInput = document.getElementById("specializationInput");

    if (user.role === "Doctor") {
        specField.style.display = "flex";
        specInput.value = user.specialization ?? "";
    } else {
        specField.style.display = "none";
        specInput.value = "";
    }
}
