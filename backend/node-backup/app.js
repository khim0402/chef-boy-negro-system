const express = require('express');
const path = require('path');
const app = express();
const authRoutes = require('./routes/auth');
const db = require('./db');

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files from public folder
app.use(express.static(path.join(__dirname, 'public')));

// Routes
app.use('/auth', authRoutes);

// Start server
app.listen(5000, () => {
  console.log('🚀 Server running on http://localhost:5000');
});
