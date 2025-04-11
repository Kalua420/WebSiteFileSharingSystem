document.addEventListener('DOMContentLoaded', function() {
    // Navigation functionality
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    const sections = document.querySelectorAll('.section');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetSection = this.getAttribute('data-section');
            
            // Remove active class from all links and sections
            navLinks.forEach(link => link.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));
            
            // Add active class to current link and section
            this.classList.add('active');
            document.getElementById(targetSection).classList.add('active');
        });
    });

    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const closeBtns = document.querySelectorAll('.close');
    
    // Show modal function
    window.showModal = function(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }
    
    // Close modals when clicking X
    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.style.display = 'none';
        });
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        modals.forEach(modal => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Side panel functionality for edit forms
    const sidePanels = document.querySelectorAll('.side-panel');
    const closePanelBtns = document.querySelectorAll('.close-panel, .btn-cancel');
    
    // Show side panel function
    window.showPanel = function(panelId, mode = 'add', data = null) {
        const panel = document.getElementById(panelId);
        panel.classList.add('active');
        
        // Update form based on mode (add or edit)
        if (mode === 'edit' && data) {
            // Set panel title
            const titleElement = panel.querySelector('.panel-header h2');
            if (titleElement) {
                titleElement.textContent = panelId === 'managerPanel' ? 'Edit Manager' : 'Edit Branch';
            }
            
            // Update form action
            const form = panel.querySelector('form');
            if (form) {
                form.action = panelId === 'managerPanel' ? 'update_manager.php' : 'update_branch.php';
                
                // Add ID field
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = data.id;
                form.appendChild(idInput);
                
                // Fill in the form fields with data
                if (panelId === 'managerPanel') {
                    form.querySelector('input[name="username"]').value = data.username;
                    form.querySelector('input[name="email"]').value = data.email;
                    form.querySelector('select[name="bid"]').value = data.bid;
                    // Show password hint
                    form.querySelector('.password-hint').style.display = 'block';
                } else if (panelId === 'branchPanel') {
                    form.querySelector('input[name="branch_name"]').value = data.branch_name;
                    form.querySelector('input[name="state"]').value = data.state;
                    form.querySelector('input[name="city"]').value = data.city;
                    form.querySelector('input[name="zip_code"]').value = data.zip_code;
                    form.querySelector('input[name="opening_date"]').value = data.opening_date;
                }
            }
        } else {
            // Reset form for add mode
            const titleElement = panel.querySelector('.panel-header h2');
            if (titleElement) {
                titleElement.textContent = panelId === 'managerPanel' ? 'Add New Manager' : 'Add New Branch';
            }
            
            const form = panel.querySelector('form');
            if (form) {
                form.reset();
                form.action = panelId === 'managerPanel' ? 'add_manager.php' : 'add_branch.php';
                
                // Remove ID field if exists
                const idInput = form.querySelector('input[name="id"]');
                if (idInput) {
                    form.removeChild(idInput);
                }
                
                // Hide password hint for add mode
                if (panelId === 'managerPanel') {
                    form.querySelector('.password-hint').style.display = 'none';
                }
            }
        }
    }
    
    // Close side panels
    closePanelBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const panel = this.closest('.side-panel');
            panel.classList.remove('active');
        });
    });
    
    // Edit branch functionality
    setupEditButtons();
    
    // Log search functionality
    setupLogSearch();
});

// Setup edit buttons for branches and managers
function setupEditButtons() {
    // Branch edit buttons
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = this.getAttribute('href');
            const id = url.split('=')[1];
            
            // Determine if this is a branch or manager edit
            const isBranch = url.includes('edit_branch.php');
            const panelId = isBranch ? 'branchPanel' : 'managerPanel';
            
            // Fetch data for editing
            fetch(`get_data.php?type=${isBranch ? 'branch' : 'manager'}&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    showPanel(panelId, 'edit', data);
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    alert('Failed to load data for editing. Please try again.');
                });
        });
    });
}

// Log search functionality
function setupLogSearch() {
    // Add behavior for log search form and buttons
    const logSearchForm = document.getElementById('logSearchForm');
    if (logSearchForm) {
        logSearchForm.addEventListener('submit', function() {
            const searchPerformed = document.createElement('input');
            searchPerformed.type = 'hidden';
            searchPerformed.name = 'search_performed';
            searchPerformed.value = 'true';
            this.appendChild(searchPerformed);
        });
    }
}

// Function to clear log search
function clearLogSearch() {
    document.getElementById('sender_id').value = '';
    document.getElementById('receiver_id').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('filename').value = '';
    
    // Add clear search parameter
    const form = document.getElementById('logSearchForm');
    const clearInput = document.createElement('input');
    clearInput.type = 'hidden';
    clearInput.name = 'clear_search';
    clearInput.value = 'true';
    form.appendChild(clearInput);
    
    form.submit();
}

// Function to generate a report based on current search
function generateLogReport() {
    // Update hidden form with current search values
    document.getElementById('report_sender_id').value = document.getElementById('sender_id').value;
    document.getElementById('report_receiver_id').value = document.getElementById('receiver_id').value;
    document.getElementById('report_email').value = document.getElementById('email').value;
    document.getElementById('report_phone').value = document.getElementById('phone').value;
    document.getElementById('report_filename').value = document.getElementById('filename').value;
    
    // Submit the report form
    document.getElementById('reportForm').submit();
}
// Example JavaScript to handle the form submission
$('#branchEditForm').submit(function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'edit_branch.php?id=' + $('#edit_branch_id').val(),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message if needed
                alert(response.message);
                
                // Redirect to the branches section
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            } else {
                // Show error message
                alert(response.error || 'An error occurred');
            }
        },
        error: function() {
            alert('An error occurred while processing your request');
        }
    });
});