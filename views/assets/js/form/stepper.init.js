document.querySelectorAll('.stepper, .form-steps-vertical').forEach(stepper => {
    const nextButtons = stepper.querySelectorAll('.nexttab');
    const prevButtons = stepper.querySelectorAll('.previestab');
    const tabs = stepper.querySelectorAll('.tab-pane');
    const progressBar = stepper.querySelector('.progress-bar');
    const tabButtons = stepper.querySelectorAll('.nav-link');
    let currentTab = 0;

    function updateTabs() {
        tabs.forEach((tab, index) => {
            tab.classList.remove('show', 'active');
            if (index === currentTab) {
                tab.classList.add('show', 'active');
            }
        });

        const isVertical = stepper.classList.contains('form-steps-vertical');

        if (isVertical) {
            const completedSteps = Math.min(currentTab, tabs.length - 1);
            const totalSteps = tabs.length - 2;
            const progressHeight = (completedSteps / totalSteps) * 100;
            progressBar.style.height = `${progressHeight}%`;
            progressBar.style.width = '100%';
        } else {
            const visibleTabs = stepper.querySelectorAll('.tab-pane:not([data-hidden="true"])');
            const progressPercentage = (currentTab / (visibleTabs.length - 1)) * 100;
            progressBar.style.width = `${progressPercentage}%`;
            progressBar.style.height = '5px';
        }
        // Update the active tab button
        tabButtons.forEach((button, index) => {
            button.classList.remove('active', 'activeComplete');
            if (index === currentTab) {
                button.classList.add('active');
                button.innerHTML = index + 1;
            } else if (index < currentTab) {
                button.classList.add('activeComplete');
                button.innerHTML = `<i class="ri-check-line"></i>`;
            } else {
                button.innerHTML = index + 1;
            }
        });
    }

    tabButtons.forEach((button, index) => {
        button.addEventListener('click', () => {
            currentTab = index;
            updateTabs();
        });
    });

    nextButtons.forEach(button => {
        button.addEventListener('click', () => {
            const visibleTabs = stepper.querySelectorAll('.tab-pane:not([data-hidden="true"])');
            if (currentTab < visibleTabs.length - 1) {
                currentTab++;
                updateTabs();
            }
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (currentTab > 0) {
                currentTab--;
                updateTabs();
            }
        });
    });

    updateTabs();
});