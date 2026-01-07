// Fetch all seminars from API
async function fetchSeminars() {
    try {
        const response = await fetch('php/api_seminars.php');
        if (!response.ok) throw new Error('Network response was not ok');
        return await response.json();
    } catch (error) {
        console.error('Error fetching seminars:', error);
        return [];
    }
}

// Fetch single seminar by ID
async function getSeminarById(id) {
    try {
        const response = await fetch(`php/api_seminars.php?id=${id}`);
        if (!response.ok) throw new Error('Network response was not ok');
        return await response.json();
    } catch (error) {
        console.error('Error fetching seminar details:', error);
        return null;
    }
}

// Helper to parse "2025-11-28" to Date object (SQL Date format)
function parseSeminarDate(dateString) {
    if (!dateString) return new Date();
    // Assuming SQL date format YYYY-MM-DD
    const parts = dateString.split('-');
    if (parts.length < 3) return new Date();
    return new Date(parts[0], parts[1] - 1, parts[2]);
}

// Format Date for Display (e.g. 28 November 2025)
function formatDisplayDate(dateString) {
    if (!dateString) return '';
    const date = parseSeminarDate(dateString);
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return date.toLocaleDateString('id-ID', options);
}

async function renderSeminars(containerId, limit = null, filterStatus = 'all', searchQuery = '') {
    const container = document.getElementById(containerId);
    if (!container) return;

    // Show loading state
    container.innerHTML = '<div style="text-align:center; padding:20px; color:white;">Loading data...</div>';

    const seminars = await fetchSeminars();
    container.innerHTML = ''; // Clear loading

    if (seminars.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:20px; color:white;">Tidak ada seminar tersedia.</div>';
        return;
    }

    let dataToRender = seminars;
    const now = new Date();

    // 1. Map Data to include computed status
    dataToRender = dataToRender.map(seminar => {
        const seminarDate = parseSeminarDate(seminar.date);

        let statusText, statusClass;

        // Compute logic real-time, ignore DB status for expiration
        const seminarEnd = new Date(seminarDate);
        if (seminar.time_end) {
            const [h, m] = seminar.time_end.split(':');
            seminarEnd.setHours(parseInt(h), parseInt(m), 59);
        } else {
            seminarEnd.setHours(23, 59, 59);
        }

        const qCurr = parseInt(seminar.quotaCurrent) || 0;
        const qMax = parseInt(seminar.quotaMax) || 0;

        if (seminarEnd < now) {
            statusText = 'SELESAI';
            statusClass = 'completed';
        } else if (qCurr >= qMax && qMax > 0) {
            statusText = 'PENUH';
            statusClass = 'full';
        } else {
            statusText = 'AKTIF';
            statusClass = 'active';
        }

        // Format date for display
        const displayDate = formatDisplayDate(seminar.date);

        return { ...seminar, statusText, statusClass, displayDate };
    });

    // 2. Filter Search Query
    if (searchQuery) {
        const q = searchQuery.toLowerCase();
        dataToRender = dataToRender.filter(s =>
            s.title.toLowerCase().includes(q) ||
            s.speaker.toLowerCase().includes(q) ||
            (s.location && s.location.toLowerCase().includes(q))
        );
    }

    // 3. Filter Data Status
    if (filterStatus !== 'all') {
        if (filterStatus === 'finished') {
            dataToRender = dataToRender.filter(s => s.statusText === 'SELESAI');
        } else if (filterStatus === 'active') {
            dataToRender = dataToRender.filter(s => s.statusText === 'AKTIF');
        } else if (filterStatus === 'full') {
            dataToRender = dataToRender.filter(s => s.statusText === 'PENUH');
        }
    }

    // 3. Slice if limit
    if (limit) {
        dataToRender = dataToRender.slice(0, limit);
    }

    // 4. Render
    dataToRender.forEach(seminar => {
        const percentage = (seminar.quotaCurrent / seminar.quotaMax) * 100;

        const cardHTML = `
            <div class="seminar-card">
                <span class="card-status ${seminar.statusClass}">${seminar.statusText}</span>
                <div class="card-img">
                    <img src="${seminar.image}" alt="Person" onerror="this.src='assets/default_seminar.jpg'">
                </div>
                <div class="card-content">
                    <h3 class="card-title">${seminar.title}</h3>

                    <div class="card-info-row person">
                        <span class="material-icons-round">person</span>
                        <span>${seminar.speaker}</span>
                    </div>
                    <div class="card-info-row">
                        <span class="material-icons-round">shield</span>
                        <span>${seminar.dosen}</span>
                    </div>
                    <div class="card-info-row">
                        <span class="material-icons-round">calendar_today</span>
                        <span>${seminar.displayDate} ${seminar.time_start ? '| ' + seminar.time_start.substring(0, 5) : ''}</span>
                    </div>
                    <div class="card-info-row location">
                        <span class="material-icons-round">place</span>
                        <span>${seminar.location}</span>
                    </div>

                    <div style="margin-top: auto;">
                        <div style="display: flex; justify-content: space-between; font-size: 10px; font-weight: 700;">
                            <span>👥 ${seminar.quotaCurrent}/${seminar.quotaMax}</span>
                        </div>
                        <div class="quota-bar">
                            <div class="quota-fill" style="width: ${percentage}%"></div>
                        </div>
                        <button class="btn-detail" onclick="window.location.href='detail.html?id=${seminar.id}'">LIHAT DETAIL</button>
                    </div>
                </div>
            </div>
        `;
        container.innerHTML += cardHTML;
    });
}
