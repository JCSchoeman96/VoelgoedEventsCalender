let link: HTMLLinkElement | null = null;

export function prefetchNext(url: string): void {
  if (!link) {
    link = document.createElement('link');
    link.rel = 'prefetch';
    document.head.appendChild(link);
  }
  link.href = url;
}
