document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id');
    const doctorSelect = document.getElementById('doctor_id');
    const dateInput = document.getElementById('appointment_date');
    const timeSlotsContainer = document.getElementById('time-slots-container');
    const selectedSlotInput = document.getElementById('selected_slot_id');
    
    // Step indicators
    const step2 = document.getElementById('step-2');
    const step3 = document.getElementById('step-3');
    const step4 = document.getElementById('step-4');

    // Summary fields
    const summaryDep = document.getElementById('summary-dep');
    const summaryDoc = document.getElementById('summary-doc');
    const summaryDate = document.getElementById('summary-date');
    const summaryTime = document.getElementById('summary-time');
    const submitBtn = document.getElementById('submit-booking');

    // Store fetched doctors to filter quickly
    let allDoctors = [];

    // Pre-populate if we are editing or have GET parameters
    async function init() {
        if (departmentSelect.value) {
            await fetchDoctors(departmentSelect.value, doctorSelect.getAttribute('data-preselect'));
            step2.style.opacity = '1';
            step2.style.pointerEvents = 'auto';
        }
        
        if (doctorSelect.value && dateInput.value) {
            fetchSlots(doctorSelect.value, dateInput.value, selectedSlotInput.value);
            step3.style.opacity = '1';
            step3.style.pointerEvents = 'auto';
        }
    }

    // 1. Department change -> Filter Doctors
    departmentSelect.addEventListener('change', async function() {
        resetSteps([2, 3, 4]);
        
        if (this.value) {
            await fetchDoctors(this.value);
            step2.style.opacity = '1';
            step2.style.pointerEvents = 'auto';
            updateSummary();
        }
    });

    // 2. Doctor change -> Unlock Date
    doctorSelect.addEventListener('change', function() {
        resetSteps([3, 4]);
        
        if (this.value) {
            step3.style.opacity = '1';
            step3.style.pointerEvents = 'auto';
            
            if (dateInput.value) {
                fetchSlots(this.value, dateInput.value);
            }
            updateSummary();
        }
    });

    // 3. Date change -> Fetch Slots
    dateInput.addEventListener('change', function() {
        resetSteps([4]);
        
        if (this.value && doctorSelect.value) {
            fetchSlots(doctorSelect.value, this.value);
            updateSummary();
        }
    });

    // Fetch doctors via AJAX (or pass via JSON on page load)
    async function fetchDoctors(depId, preselectId = null) {
        doctorSelect.innerHTML = '<option value="">Эмч сонгох...</option>';
        try {
            const response = await fetch(`get_doctors_ajax.php?department_id=${depId}`);
            const doctors = await response.json();
            
            doctors.forEach(doc => {
                const option = document.createElement('option');
                option.value = doc.id;
                option.textContent = `Др. ${doc.full_name} (${doc.specialization || 'Туслах'})`;
                doctorSelect.appendChild(option);
            });

            if (preselectId) {
                doctorSelect.value = preselectId;
            }
        } catch (error) {
            console.error('Error fetching doctors:', error);
        }
    }

    // Fetch available slots
    async function fetchSlots(docId, dateStr, preselectSlotId = null) {
        timeSlotsContainer.innerHTML = '<div style="color: #64748b;">Цаг шалгаж байна...</div>';
        
        try {
            const response = await fetch(`get_slots.php?doctor_id=${docId}&date=${dateStr}`);
            const data = await response.json();
            
            timeSlotsContainer.innerHTML = '';
            
            if (data.error) {
                const errDiv = document.createElement('div');
                errDiv.style.color = 'red';
                errDiv.textContent = data.error;
                timeSlotsContainer.appendChild(errDiv);
                return;
            }

            if (!data.slots || data.slots.length === 0) {
                timeSlotsContainer.innerHTML = '<div style="color: #f59e0b; padding: 10px; background: #fef3c7; border-radius: 5px;">Энэ өдөр сул цаг байхгүй байна.</div>';
                return;
            }

            const slotGrid = document.createElement('div');
            slotGrid.style.display = 'flex';
            slotGrid.style.gap = '10px';
            slotGrid.style.flexWrap = 'wrap';

            data.slots.forEach(slot => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'slot-btn';
                btn.textContent = slot.time.substring(0, 5);
                btn.dataset.id = slot.id;
                
                // Style
                btn.style.padding = '10px 20px';
                btn.style.border = '1px solid #cbd5e1';
                btn.style.borderRadius = '5px';
                btn.style.background = '#f8fafc';
                btn.style.cursor = 'pointer';
                btn.style.transition = 'all 0.2s';
                
                btn.addEventListener('mouseenter', () => { if(selectedSlotInput.value != slot.id) btn.style.background = '#e2e8f0'; });
                btn.addEventListener('mouseleave', () => { if(selectedSlotInput.value != slot.id) btn.style.background = '#f8fafc'; });

                if (preselectSlotId && preselectSlotId == slot.id) {
                    selectSlot(btn, slot);
                }

                btn.addEventListener('click', () => selectSlot(btn, slot));
                slotGrid.appendChild(btn);
            });

            timeSlotsContainer.appendChild(slotGrid);
        } catch (error) {
            console.error('Error fetching slots:', error);
            timeSlotsContainer.innerHTML = '<div style="color: red;">Алдаа гарлаа.</div>';
        }
    }

    function selectSlot(btn, slot) {
        // visually deselect all
        document.querySelectorAll('.slot-btn').forEach(b => {
            b.style.background = '#f8fafc';
            b.style.borderColor = '#cbd5e1';
            b.style.color = '#000';
        });

        // select current
        btn.style.background = '#0284c7';
        btn.style.borderColor = '#0284c7';
        btn.style.color = '#fff';

        selectedSlotInput.value = slot.id;
        
        // Unlock Step 4
        step4.style.opacity = '1';
        step4.style.pointerEvents = 'auto';
        submitBtn.disabled = false;
        
        // update summary
        summaryTime.textContent = slot.time.substring(0, 5);
        updateSummary();
    }

    function updateSummary() {
        if (departmentSelect.value) {
            summaryDep.textContent = departmentSelect.options[departmentSelect.selectedIndex].text;
        } else {
            summaryDep.textContent = '-';
        }

        if (doctorSelect.value) {
            summaryDoc.textContent = doctorSelect.options[doctorSelect.selectedIndex].text;
        } else {
            summaryDoc.textContent = '-';
        }

        if (dateInput.value) {
            summaryDate.textContent = dateInput.value;
        } else {
            summaryDate.textContent = '-';
        }
    }

    function resetSteps(steps) {
        if (steps.includes(2)) {
            doctorSelect.innerHTML = '<option value="">Эхлээд тасаг сонгоно уу</option>';
            step2.style.opacity = '0.5';
            step2.style.pointerEvents = 'none';
        }
        if (steps.includes(3)) {
            timeSlotsContainer.innerHTML = '<div style="color: #94a3b8;">Эмч болон огноо сонгохыг хүлээнэ үү...</div>';
            selectedSlotInput.value = '';
            step3.style.opacity = '0.5';
            step3.style.pointerEvents = 'none';
        }
        if (steps.includes(4)) {
            step4.style.opacity = '0.5';
            step4.style.pointerEvents = 'none';
            submitBtn.disabled = true;
            summaryTime.textContent = '-';
        }
        updateSummary();
    }

    // Initialize
    init();
});