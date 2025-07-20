# Voelgoed Events Calendar

## Database Optimization

To improve query performance, create indexes on frequently queried meta keys using the following SQL:

```sql
ALTER TABLE wp_postmeta ADD INDEX idx_meta_datum (meta_key(191), meta_value(10));
ALTER TABLE wp_postmeta ADD INDEX idx_meta_venue (meta_key(191), meta_value(50));
```

Run these commands via WP-CLI or phpMyAdmin before activating version 4.1.
