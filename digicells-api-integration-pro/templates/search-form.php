<div class="digicells-search-container">
    <form id="digicells-hotel-search-form" class="digicells-search-form">
        <div class="digicells-form-row">
            <div class="digicells-form-group">
                <label for="destination">Destination City Code</label>
                <input type="text" id="destination" name="destination" placeholder="PAR, NYC, LON, DXB" required maxlength="3">
                <small>Enter 1-3 letter city code (e.g., PAR for Paris)</small>
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
                    <option value="1">1 Room</option>
                    <option value="2">2 Rooms</option>
                    <option value="3">3 Rooms</option>
                    <option value="4">4 Rooms</option>
                    <option value="5">5 Rooms</option>
                </select>
            </div>
            
            <div class="digicells-form-group">
                <label for="adults">Adults</label>
                <select id="adults" name="adults">
                    <option value="1">1 Adult</option>
                    <option value="2" selected>2 Adults</option>
                    <option value="3">3 Adults</option>
                    <option value="4">4 Adults</option>
                </select>
            </div>
            
            <div class="digicells-form-group">
                <label for="children">Children</label>
                <select id="children" name="children">
                    <option value="0" selected>0 Children</option>
                    <option value="1">1 Child</option>
                    <option value="2">2 Children</option>
                    <option value="3">3 Children</option>
                </select>
            </div>
            
            <div class="digicells-form-group digicells-search-button-group">
                <button type="submit" class="digicells-search-button">🔍 Search Hotels</button>
            </div>
        </div>
    </form>
    
    <div id="digicells-search-loader" class="digicells-loader" style="display: none;">
        <div class="spinner"></div>
        <p>Searching hotels...</p>
    </div>
    
    <div id="digicells-search-results"></div>
</div>