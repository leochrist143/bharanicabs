document.addEventListener('DOMContentLoaded', function() {
  const LOCATIONIQ_API_KEY = 'pk.ec16890502bc14c36f0fd4c82bf13012'; // Replace with your key
  const DEBOUNCE_DELAY = 300;
  const TAMILNADU_BOUNDS = "8.078,76.244,13.339,80.347"; // Approximate bounds for Tamil Nadu

  // Initialize autocomplete for both inputs
  initAutocomplete('pickupLocation', 'pickupSuggestions', 'pickupLat', 'pickupLon');
  initAutocomplete('destination', 'destinationSuggestions', 'destinationLat', 'destinationLon');

  function initAutocomplete(inputId, suggestionsId, latId, lonId) {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);
    const latInput = document.getElementById(latId);
    const lonInput = document.getElementById(lonId);
    let debounceTimer;

    input.addEventListener('input', function() {
      clearTimeout(debounceTimer);
      const query = input.value.trim();
      
      if (query.length < 3) {
        suggestions.classList.add('hidden');
        return;
      }

      debounceTimer = setTimeout(() => {
        fetchLocations(query, suggestions, latInput, lonInput, input);
      }, DEBOUNCE_DELAY);
    });

      // Mobile-friendly touch handling
      suggestions.addEventListener('touchstart', function(e) {
        e.stopPropagation(); // Prevent document click from hiding suggestions
      }, { passive: true });
  
      suggestions.addEventListener('touchmove', function(e) {
        e.stopPropagation(); // Allow scrolling without closing dropdown
      }, { passive: true });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
      if (!input.contains(e.target) && !suggestions.contains(e.target)) {
        suggestions.classList.add('hidden');
      }
    });
  }

  async function fetchLocations(query, suggestions, latInput, lonInput, input) {
    try {
      // Encode query for URL
      const encodedQuery = encodeURIComponent(query);
      const url = `https://us1.locationiq.com/v1/autocomplete?key=${LOCATIONIQ_API_KEY}&q=${encodedQuery}&limit=5&countrycodes=in`;
      
      const response = await fetch(url);
      const data = await response.json();
      
      suggestions.innerHTML = '';
      
      if (data.length === 0) {
        suggestions.innerHTML = '<div class="p-2 text-gray-500">No results found in Tamil Nadu</div>';
        suggestions.classList.remove('hidden');
        return;
      }

      data.forEach(item => {
        const suggestion = document.createElement('div');
        suggestion.className = 'p-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100';
        
        // Format display text for Tamil Nadu locations
        // let displayText = item.display_name;
        // if (item.address) {
        //   displayText = [
        //     item.address.road,
        //     item.address.neighbourhood,
        //     item.address.suburb,
        //     item.address.city || item.address.town || item.address.village,
        //     item.address.state
        //   ].filter(Boolean).join(', ');
        // }
        
        suggestion.textContent = item.display_name;
        
        suggestion.addEventListener('click', () => {
          input.value = displayText;
          latInput.value = item.lat;
          lonInput.value = item.lon;
          suggestions.classList.add('hidden');
          validateLocations();
        });
        
        suggestions.appendChild(suggestion);
      });
      
      suggestions.classList.remove('hidden');
    } catch (error) {
      console.error('Error fetching locations:', error);
      suggestions.innerHTML = '<div class="p-2 text-red-500">Error loading suggestions</div>';
      suggestions.classList.remove('hidden');
    }
  }

  function validateLocations() {
    const errorElement = document.getElementById('locationError');
    const pickupLat = parseFloat(document.getElementById('pickupLat').value);
    const pickupLon = parseFloat(document.getElementById('pickupLon').value);
    const destLat = parseFloat(document.getElementById('destinationLat').value);
    const destLon = parseFloat(document.getElementById('destinationLon').value);
    
    errorElement.classList.add('hidden');
    
    if (isNaN(pickupLat) || isNaN(pickupLon) || isNaN(destLat) || isNaN(destLon)) {
      return; // Not enough data to validate yet
    }
    
    const distance = calculateDistance(pickupLat, pickupLon, destLat, destLon);
    
    if (distance < 0.1) { // 0.1 km = 100 meters
      errorElement.textContent = 'Pickup and destination must be at least 100 meters apart';
      errorElement.classList.remove('hidden');
    }
  }

  function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
      Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c; // Distance in km
  }
});
