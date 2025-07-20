export async function fetchEvents(url) {
  const res = await fetch(url);
  return res.json();
}
