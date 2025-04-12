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
            
            // Update URL without refreshing the page
            const newUrl = window.location.origin + window.location.pathname + '?section=' + targetSection;
            history.pushState({section: targetSection}, '', newUrl);
        });
    });
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.section) {
            const targetSection = event.state.section;
            
            // Remove active class from all links and sections
            navLinks.forEach(link => link.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));
            
            // Add active class to current link and section
            const targetLink = document.querySelector(`.sidebar-nav a[data-section="${targetSection}"]`);
            if (targetLink) {
                targetLink.classList.add('active');
                document.getElementById(targetSection).classList.add('active');
            }
        }
    });

    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const modalCloseButtons = document.querySelectorAll('.modal .close');
    
    window.showModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            // Reset form when opening modal
            const form = modal.querySelector('form');
            if (form) form.reset();
        }
    };
    
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.style.display = 'none';
        });
    });
    
    // Close modal when clicking outside of content
    window.addEventListener('click', function(event) {
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Side panel functionality for edit forms
    const sidePanels = document.querySelectorAll('.side-panel');
    const closePanelBtns = document.querySelectorAll('.close-panel, .btn-cancel');
    
    // Show side panel function - IMPROVED
    window.showPanel = function(panelId, mode = 'add', data = null) {
        console.log("Opening panel:", panelId, "Mode:", mode, "Data:", data);
        
        const panel = document.getElementById(panelId);
        if (!panel) {
            console.error("Panel not found:", panelId);
            return;
        }
        
        panel.classList.add('active');
        
        // Update form based on mode (add or edit)
        if (mode === 'edit' && data) {
            console.log("Setting up edit mode with data:", data);
            
            // Set panel title
            const titleElement = panel.querySelector('.panel-header h2');
            if (titleElement) {
                titleElement.textContent = panelId === 'managerPanel' ? 'Edit Manager' : 'Edit Branch';
            }
            
            // Get the form
            const form = panel.querySelector('form');
            if (form) {
                // Reset form first to clear any previous data
                form.reset();
                
                // Update form action
                form.action = panelId === 'managerPanel' ? 'update_manager.php' : 'update_branch.php';
                
                // Remove any existing ID field
                const existingIdInput = form.querySelector('input[name="id"]');
                if (existingIdInput) {
                    existingIdInput.remove();
                }
                
                // Add ID field
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = data.id;
                form.appendChild(idInput);
                console.log("Added hidden ID field with value:", data.id);
                
                // Fill in the form fields with data
                if (panelId === 'managerPanel') {
                    setFormFieldValue(form, 'input[name="username"]', data.username);
                    setFormFieldValue(form, 'input[name="email"]', data.email);
                    
                    // Handle bid - make sure it exists
                    const bidSelect = form.querySelector('select[name="bid"]');
                    if (bidSelect && data.bid) {
                        // Find and select the option with matching value
                        const options = bidSelect.options;
                        for (let i = 0; i < options.length; i++) {
                            if (options[i].value == data.bid) {
                                bidSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                    
                    // Show password hint
                    const passwordHint = form.querySelector('.password-hint');
                    if (passwordHint) {
                        passwordHint.style.display = 'block';
                    }
                } else if (panelId === 'branchPanel') {
                    setFormFieldValue(form, 'input[name="branch_name"]', data.branch_name);
                    setFormFieldValue(form, 'input[name="state"]', data.state);
                    setFormFieldValue(form, 'input[name="city"]', data.city);
                    setFormFieldValue(form, 'input[name="zip_code"]', data.zip_code);
                    
                    // Handle date field
                    setFormFieldValue(form, 'input[name="opening_date"]', data.opening_date);
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
                    idInput.remove();
                }
                
                // Hide password hint for add mode
                const passwordHint = form.querySelector('.password-hint');
                if (passwordHint) {
                    passwordHint.style.display = 'none';
                }
            }
        }
    }
    
    // Helper function to safely set form field values
    function setFormFieldValue(form, selector, value) {
        const field = form.querySelector(selector);
        if (field && value !== undefined && value !== null) {
            field.value = value;
        }
    }
    
    // Close side panels
    closePanelBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const panel = this.closest('.side-panel');
            panel.classList.remove('active');
        });
    });
    
    // Setup edit buttons for branches and managers
    setupEditButtons();
    
    // Setup form submissions
    setupFormSubmissions();
    
    // Removed: Setup log search functionality
    // setupLogSearch();
    
    // Check URL on page load and activate correct section
    const urlParams = new URLSearchParams(window.location.search);
    const sectionParam = urlParams.get('section');
    if (sectionParam) {
        const sectionLink = document.querySelector(`.sidebar-nav a[data-section="${sectionParam}"]`);
        if (sectionLink) {
            sectionLink.click();
        }
    }
});

// Setup edit buttons for branches and managers - IMPROVED
function setupEditButtons() {
    // Branch and manager edit buttons
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get data from onclick attribute
            const dataStr = this.getAttribute('onclick');
            if (!dataStr) return;
            
            // Extract data from function call
            const match = dataStr.match(/showPanel\('(\w+)', '(\w+)', (\{.+?\})\)/);
            if (!match) return;
            
            const panelId = match[1];
            const mode = match[2];
            let data;
            
            try {
                // Parse the data object from the string representation
                // This is safer than using eval
                data = JSON.parse(match[3].replace(/'/g, '"'));
            } catch (error) {
                console.error('Error parsing data:', error);
                return;
            }
            
            // Call showPanel directly instead of making an AJAX request
            showPanel(panelId, mode, data);
        });
    });
}

function setupFormSubmissions() {
    // Generic form handler for both manager and branch forms
    function setupFormHandler(formSelector, panelId) {
        const form = document.querySelector(formSelector);
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const action = this.action;
                
                // Show loading indicator
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Processing...';
                submitBtn.disabled = true;
                
                fetch(action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Create and show notification
                        showNotification(data.message || 'Operation successful', 'success');
                        
                        // Close the panel
                        document.getElementById(panelId).classList.remove('active');
                        
                        // Use redirect URL if provided, otherwise reload current page
                        setTimeout(() => {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.reload();
                            }
                        }, 1000);
                    } else {
                        showNotification(data.error || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    console.error("Error submitting form:", error);
                    showNotification(`Failed: ${error.message}`, 'error');
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
            });
        }
    }
    
    // Setup both forms
    setupFormHandler('#managerPanel form', 'managerPanel');
    setupFormHandler('#branchPanel form', 'branchPanel');
}

// Function to show notifications
function showNotification(message, type = 'info') {
    // Check if notification container exists, if not create it
    let notifContainer = document.getElementById('notification-container');
    if (!notifContainer) {
        notifContainer = document.createElement('div');
        notifContainer.id = 'notification-container';
        notifContainer.style.position = 'fixed';
        notifContainer.style.top = '20px';
        notifContainer.style.right = '20px';
        notifContainer.style.zIndex = '1000';
        document.body.appendChild(notifContainer);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Style the notification
    notification.style.backgroundColor = type === 'success' ? '#4CAF50' : 
                                         type === 'error' ? '#F44336' : '#2196F3';
    notification.style.color = 'white';
    notification.style.padding = '15px 20px';
    notification.style.marginBottom = '10px';
    notification.style.borderRadius = '5px';
    notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
    notification.style.display = 'flex';
    notification.style.justifyContent = 'space-between';
    notification.style.alignItems = 'center';
    notification.style.minWidth = '250px';
    notification.style.maxWidth = '350px';
    notification.style.animation = 'slideIn 0.5s forwards';
    
    // Add close button handler
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.style.cursor = 'pointer';
    closeBtn.style.background = 'none';
    closeBtn.style.border = 'none';
    closeBtn.style.fontSize = '20px';
    closeBtn.style.color = 'white';
    closeBtn.style.marginLeft = '10px';
    
    closeBtn.addEventListener('click', function() {
        notification.style.animation = 'slideOut 0.5s forwards';
        setTimeout(() => {
            notifContainer.removeChild(notification);
        }, 500);
    });
    
    // Add to container
    notifContainer.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode === notifContainer) {
            notification.style.animation = 'slideOut 0.5s forwards';
            setTimeout(() => {
                if (notification.parentNode === notifContainer) {
                    notifContainer.removeChild(notification);
                }
            }, 500);
        }
    }, 5000);
    
    // Add CSS animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Logs Section Section Handling 
// Enhanced JavaScript functions for better URL handling
function clearLogSearch() {
    // Redirect to the logs section without search parameters
    window.location.href = '?section=logs';
}

function generateLogReport() {
    // Update the hidden form fields with current search values
    document.getElementById('report_sender_id').value = document.getElementById('sender_id').value;
    document.getElementById('report_receiver_id').value = document.getElementById('receiver_id').value;
    document.getElementById('report_email').value = document.getElementById('email').value;
    document.getElementById('report_phone').value = document.getElementById('phone').value;
    document.getElementById('report_filename').value = document.getElementById('filename').value;
    
    // Submit the form
    document.getElementById('reportForm').submit();
}

// Add this function to handle section navigation with search preservation
document.addEventListener('DOMContentLoaded', function() {
    // Handle section navigation
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            
            // If clicking on logs section, preserve search params
            if (section === 'logs' && window.location.search.includes('search_performed=true')) {
                // Keep the current URL with search parameters
                showSection(section);
            } else {
                // For other sections or no search, simple navigation
                window.location.href = '?section=' + section;
            }
        });
    });
    
    // Check URL parameters on page load to set active section
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    if (section) {
        showSection(section);
    }
    
    // Add search_performed parameter on form submit
    const form = document.getElementById('logSearchForm');
    if (form) {
        form.addEventListener('submit', function() {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'search_performed';
            input.value = 'true';
            form.appendChild(input);
        });
    }
});

// Function to show a section
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show the target section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Update active nav link
    document.querySelectorAll('.sidebar-nav a').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-section') === sectionId) {
            link.classList.add('active');
        }
    });
}