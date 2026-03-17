document.addEventListener("DOMContentLoaded", () => {
    // Toggle Sidebar
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");

    sidebarToggle.addEventListener("click", () => {
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle("active");
        } else {
            sidebar.classList.toggle("collapsed");
        }
    });

    // Validação básica de formulário
    const forms = document.querySelectorAll("form");
    forms.forEach(form => {
        form.addEventListener("submit", (e) => {
            let isValid = true;
            const requiredFields = form.querySelectorAll("[required]");
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = "#e74c3c";
                } else {
                    field.style.borderColor = "#e0e6ed";
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert("Por favor, preencha todos os campos obrigatórios.");
            }
        });
    });
});