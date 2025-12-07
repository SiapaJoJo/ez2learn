USE ez2learn;

UPDATE users 
SET password = '$2y$10$oF4CCiANw3XH9Ht5Uliv8OPXVjLfvw1d9rpeBT9j2iwqsg.i0CGIm'
WHERE username = 'admin' AND role = 'admin';

