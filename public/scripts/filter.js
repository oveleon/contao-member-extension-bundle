window.onload = () => {
  document.querySelectorAll('.member-filter-form input[type=checkbox]').forEach(checkbox => {
    checkbox.addEventListener('change', el => {
      el.currentTarget.closest('form')?.submit()
    })
  })
}
