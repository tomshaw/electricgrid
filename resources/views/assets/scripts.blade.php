<script>
  window.ElectricGrid = {
    baseUrl: '{{ url('/') }}',
    csrfToken: '{{ csrf_token() }}',
  };

  document.addEventListener('alpine:init', () => {
    Alpine.data('electricGridInfiniteScroll', () => ({
      observer: null,
      init() {
        this.observer = new IntersectionObserver(([entry]) => {
          if (entry.isIntersecting) {
            this.$wire.loadMore();
          }
        }, { rootMargin: '200px' });
        this.observer.observe(this.$el);
      },
      destroy() {
        this.observer?.disconnect();
      },
    }));
  });
</script>