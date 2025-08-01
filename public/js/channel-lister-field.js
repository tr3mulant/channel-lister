document.addEventListener("DOMContentLoaded", function () {
  const searchForm = document.getElementById("search-form");
  const clearButton = document.getElementById("clear-search");
  const loadingIndicator = document.getElementById("loading-indicator");
  const tableContainer = document.getElementById("table-container");

  let currentSearchParams = new URLSearchParams();
  let searchTimeout;

  // Search form submission
  if (searchForm) {
    console.log("Search form found, initializing search functionality.");
    searchForm.addEventListener("submit", function (e) {
      e.preventDefault();
      performSearch(true); // Reset to page 1 on form submission
    });

    // Real-time search on input (with debounce)
    const searchInput = document.getElementById("search");
    if (searchInput) {
      searchInput.addEventListener("input", function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          performSearch(true); // Reset to page 1 on search input
        }, 300);
      });
    }

    // Trigger search on filter changes
    const filterSelects = searchForm.querySelectorAll("select");
    filterSelects.forEach((select) => {
      select.addEventListener("change", () => performSearch(true)); // Reset to page 1 on filter change
    });
  }

  // Clear search
  if (clearButton) {
    clearButton.addEventListener("click", function () {
      searchForm.reset();
      currentSearchParams = new URLSearchParams();
      performSearch(true); // Reset to page 1 when clearing search
    });
  }

  // Handle pagination clicks
  document.addEventListener("click", function (e) {
    if (e.target.closest(".pagination a")) {
      e.preventDefault();
      const url = new URL(e.target.closest(".pagination a").href);
      const page = url.searchParams.get("page");
      if (page) {
        console.log(`Navigating to page ${page}`);
        currentSearchParams.set("page", page);
        performSearch(false); // Don't reset page - we're explicitly setting it
      }
    }
  });

  function performSearch(resetPage = false) {
    showLoading();

    // Collect form data
    const searchUrl = searchForm.dataset.searchUrl;
    const formData = new FormData(searchForm);
    const searchParams = new URLSearchParams();

    for (let [key, value] of formData.entries()) {
      if (value.trim() !== "") {
        searchParams.set(key, value);
      }
    }

    // Handle pagination
    if (resetPage) {
      // Reset to page 1 for new searches
      searchParams.set("page", "1");
    } else if (currentSearchParams.has("page") && !searchParams.has("page")) {
      // Preserve current page for pagination clicks
      searchParams.set("page", currentSearchParams.get("page"));
    }

    currentSearchParams = searchParams;

    // Make AJAX request
    fetch(`${searchUrl}?${searchParams.toString()}`, {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          updateTable(data.data);
        } else {
          console.error("Search failed:", data.message);
        }
      })
      .catch((error) => {
        console.error("Search error:", error);
      })
      .finally(() => {
        hideLoading();
      });
  }

  function updateTable(data) {
    // Update table body
    const tableBody = document.getElementById("table-body");
    if (tableBody) {
      tableBody.innerHTML = data.table_html;
    }

    // Update pagination
    const paginationContainer = document.getElementById("pagination-container");
    if (paginationContainer) {
      paginationContainer.innerHTML = data.pagination_html;
    }

    // Update URL without page reload
    const url = new URL(window.location);
    currentSearchParams.forEach((value, key) => {
      url.searchParams.set(key, value);
    });

    // Remove empty parameters
    for (let [key, value] of url.searchParams.entries()) {
      if (!value.trim()) {
        url.searchParams.delete(key);
      }
    }

    window.history.pushState({}, "", url);
  }

  function showLoading() {
    if (loadingIndicator) {
      loadingIndicator.style.display = "block";
    }
    if (tableContainer) {
      tableContainer.style.opacity = "0.5";
    }
  }

  function hideLoading() {
    if (loadingIndicator) {
      loadingIndicator.style.display = "none";
    }
    if (tableContainer) {
      tableContainer.style.opacity = "1";
    }
  }
});
