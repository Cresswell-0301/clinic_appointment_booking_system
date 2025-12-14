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

    const roleHidden = document.getElementById("roleHidden");
    const roleTitle = document.getElementById("modalRoleTitle");

    const specField = document.getElementById("specializationField");
    const specInput = document.getElementById("specializationInput");

    roleHidden.value = role;
    roleTitle.innerText = role;

    document.getElementById("modalRoleTitle").innerText = role;

    if (role === "Doctor") {
        specField.style.display = "flex";
        specInput.required = true;
    } else {
        specField.style.display = "none";
        specInput.required = false;
        specInput.value = "";
    }
}
