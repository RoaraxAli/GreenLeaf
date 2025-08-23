// Main JavaScript file for Greenleaf application

document.addEventListener("DOMContentLoaded", () => {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new window.bootstrap.Tooltip(tooltipTriggerEl))

  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert:not(.alert-permanent)")
  alerts.forEach((alert) => {
    setTimeout(() => {
      const bsAlert = new window.bootstrap.Alert(alert)
      bsAlert.close()
    }, 5000)
  })

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault()
      const target = document.querySelector(this.getAttribute("href"))
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        })
      }
    })
  })

  // Add loading spinner to forms on submit
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", () => {
      const submitBtn = form.querySelector('button[type="submit"]')
      if (submitBtn) {
        const originalText = submitBtn.innerHTML
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...'
        submitBtn.disabled = true

        // Re-enable after 10 seconds as fallback
        setTimeout(() => {
          submitBtn.innerHTML = originalText
          submitBtn.disabled = false
        }, 10000)
      }
    })
  })

  // Image lazy loading fallback
  const images = document.querySelectorAll("img[data-src]")
  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target
        img.src = img.dataset.src
        img.classList.remove("lazy")
        imageObserver.unobserve(img)
      }
    })
  })

  images.forEach((img) => imageObserver.observe(img))

  // Search functionality
  const searchInput = document.getElementById("searchInput")
  if (searchInput) {
    let searchTimeout
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        const query = this.value.trim()
        if (query.length >= 2) {
          // Implement search functionality
          performSearch(query)
        }
      }, 300)
    })
  }

  // Cart functionality
  window.addToCart = (plantId, quantity = 1) => {
    fetch("api/cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "add",
        plant_id: plantId,
        quantity: quantity,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          window.updateCartBadge(data.cart_count)
          window.showNotification("Plant added to cart!", "success")
        } else {
          window.showNotification(data.message || "Error adding to cart", "error")
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        window.showNotification("Error adding to cart", "error")
      })
  }

  // Update cart badge
  window.updateCartBadge = (count) => {
    const badge = document.querySelector(".cart-badge")
    if (badge) {
      if (count > 0) {
        badge.textContent = count
        badge.style.display = "flex"
      } else {
        badge.style.display = "none"
      }
    }
  }

  // Show notification
  window.showNotification = (message, type = "info") => {
    const notification = document.createElement("div")
    notification.className = `alert alert-${type === "error" ? "danger" : type} alert-dismissible fade show position-fixed`
    notification.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;"
    notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `

    document.body.appendChild(notification)

    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification)
      }
    }, 5000)
  }
})

// Search function
function performSearch(query) {
  // This would typically make an AJAX call to search.php
  console.log("Searching for:", query)
}

// Format price
function formatPrice(price) {
  return "$" + Number.parseFloat(price).toFixed(2)
}

// Validate email
function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}

// Debounce function
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}
