<div class="digicells-search-container">
    <form id="digicells-hotel-search-form" class="digicells-search-form">
        <div class="digicells-form-row">
            <div class="digicells-form-group">
                <label for="destination">Destination City Code</label>
                <input type="text" id="destination" name="destination" placeholder="e.g., PAR, NYC, DXB, LON" required>
                <small>Enter airport/city code (e.g., PAR for Paris)</small>
            </div>
            
            <div class="digicells-form-group">
                <label for="check_in">Check-in Date</label>
                <input type="date" id="check_in" name="check_in" required>
            </div>
            
            <div class="digicells-form-group">
                <label for="check_out">Check-out Date</label>
                <input type="date" id="check_out" name="check_out" required>
            </div>
        </div>
        
        <div class="digicells-form-row">
            <div class="digicells-form-group">
                <label for="rooms">Rooms</label>
                <select id="rooms" name="rooms">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> Room<?php echo $i > 1 ? 's' : ''; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="digicells-form-group">
                <label for="adults">Adults</label>
                <select id="adults" name="adults">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> Adult<?php echo $i > 1 ? 's' : ''; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="digicells-form-group">
                <label for="children">Children</label>
                <select id="children" name="children">
                    <?php for ($i = 0; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> Child<?php echo $i != 1 ? 'ren' : ''; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="digicells-form-group digicells-search-button-group">
                <button type="submit" class="digicells-search-button">Search Hotels</button>
            </div>
        </div>
    </form>
    
    <div id="digicells-search-loader" class="digicells-loader" style="display: none;">
        <div class="spinner"></div>
        <p>Searching hotels...</p>
    </div>
    
    <div id="digicells-search-results"></div>
</div>