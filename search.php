<?php
$default_common_css = 'assets/css/common.css'; // Fix relative path for root file
$page_css = 'assets/css/search.css';
$page_class = 'search-page';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <!-- Hero Banner -->
    <div class="search-hero" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; text-align: center; box-shadow: 0 10px 30px rgba(13, 110, 253, 0.2);">
        <h1 style="font-size: 2.5rem; margin-bottom: 10px;">Find Your Perfect Drive</h1>
        <p style="font-size: 1.1rem; opacity: 0.9;">Choose from our premium fleet for your next journey</p>
    </div>

    <div class="search-container">
        <!-- Sidebar Filters -->
        <aside class="search-sidebar">
            <h3 style="margin-bottom: 20px;">Refine Search</h3>
            
            <form id="filterForm">
                <div class="filter-group">
                    <h4>Location</h4>
                    <input type="text" name="location" class="search-input" placeholder="City or Airport...">
                    <label style="margin-top:10px;display:flex;align-items:center;font-size:0.9em;color:#666;">
                        <input type="checkbox" name="only_available" value="1" style="margin-right:8px;"> Show only currently available
                    </label>
                </div>

                <div class="filter-group">
                    <h4>Date Range</h4>
                    <input type="date" name="start_date" class="search-input" style="margin-bottom:5px;">
                    <input type="date" name="end_date" class="search-input">
                </div>

                <div class="filter-group">
                    <h4>Price per Day</h4>
                    <input type="range" name="max_price" min="20" max="5000" value="5000" class="range-slider" id="priceRange" oninput="document.getElementById('priceVal').innerText = '₹' + this.value">
                    <div class="price-display">
                        <span>₹0</span>
                        <span id="priceVal">₹5000</span>
                    </div>
                </div>

                <div class="filter-group">
                    <h4>Vehicle Type</h4>
                    <label class="filter-option">
                        <input type="checkbox" name="type[]" value="SUV"> SUV
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="type[]" value="Sedan"> Sedan
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="type[]" value="Luxury"> Luxury
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="type[]" value="Sports"> Sports
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="type[]" value="Scooter"> Scooter
                    </label>
                </div>

                <div class="filter-group">
                    <h4>Transmission</h4>
                    <label class="filter-option">
                        <input type="radio" name="transmission" value="Auto"> Automatic
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="transmission" value="Manual"> Manual
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="transmission" value="" checked> Any
                    </label>
                </div>

                <div class="filter-group">
                    <h4>Features</h4>
                    <label class="filter-option">
                        <input type="checkbox" name="features[]" value="Bluetooth"> Bluetooth
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="features[]" value="GPS"> GPS
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="features[]" value="Camera"> Backup Camera
                    </label>
                </div>
                
                <button type="button" class="btn-primary" onclick="searchVehicles()" style="width:100%">Apply Filters</button>
            </form>
        </aside>

        <!-- Results Area -->
        <section class="search-results">
            <div class="results-header">
                <h2 id="resultsCount">Loading vehicles...</h2>
                <div class="sort-options">
                    <select class="search-input" style="width: auto;" onchange="searchVehicles()" name="sort">
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                    </select>
                </div>
            </div>

            <div id="vehicleGrid" class="vehicle-grid">
                <!-- JS will populate this -->
            </div>
        </section>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        searchVehicles(); // Load initial results
    });

    function searchVehicles() {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams(formData);

        // Grid loader
        document.getElementById('vehicleGrid').innerHTML = '<p>Searching...</p>';

        fetch('api/search_vehicles.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                const grid = document.getElementById('vehicleGrid');
                const count = document.getElementById('resultsCount');
                grid.innerHTML = '';
                
                count.innerText = `${data.length} vehicles found`;

                if (data.length === 0) {
                    grid.innerHTML = '<p>No vehicles found matching your criteria.</p>';
                    return;
                }

                data.forEach(car => {
                    const card = document.createElement('div');
                    card.className = 'vehicle-card';
                    card.innerHTML = `
                        <div class="vehicle-img" style="background-image: url('${car.image ? 'uploads/vehicles/' + car.image : 'assets/img/default_car.jpg'}')">
                            <span class="vehicle-price-badge">₹${car.price_per_day}/day</span>
                        </div>
                        <div class="vehicle-info">
                            <h3 class="vehicle-title">${car.name}</h3>
                            <div class="vehicle-type">${car.type} • ${car.transmission || 'Auto'}</div>
                            <div class="vehicle-features-preview">
                                <span>📍 ${car.location || 'Main Branch'}</span>
                                <span>⛽ ${car.fuel_type || 'Petrol'}</span>
                            </div>
                            <div class="vehicle-card-footer">
                                <div class="rating-stars">
                                    ${'★'.repeat(Math.round(car.avg_rating || 5))}${'☆'.repeat(5 - Math.round(car.avg_rating || 5))}
                                    <span style="color:#888;font-size:0.8em">(${car.review_count || 0})</span>
                                </div>
                                <a href="vehicle_details.php?id=${car.id}" class="btn-book">View Deal</a>
                            </div>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            })
            .catch(err => {
                console.error('Error:', err);
                document.getElementById('vehicleGrid').innerHTML = '<p>Error loading vehicles.</p>';
            });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
