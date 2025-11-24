const express = require('express');
const router = express.Router();
const db = require('../db'); // your MySQL connection
const jwt = require('jsonwebtoken');

router.post('/login', (req, res) => {
  const { email, password } = req.body;

  if (!email || !password) {
    return res.status(400).json({ message: 'All fields are required' });
  }

  // ✅ SHA2 password check
  const sql = 'SELECT * FROM users WHERE email = ? AND password = SHA2(?, 256)';

  db.query(sql, [email, password], (err, results) => {
    if (err) {
      console.error('❌ Database error:', err);
      return res.status(500).json({ message: 'Database error', error: err.message });
    }

    if (!results || results.length === 0) {
      return res.status(401).json({ message: 'Invalid email or password' });
    }

    const user = results[0];

    try {
      const token = jwt.sign(
        { id: user.user_id, role: user.role },
        'your_jwt_secret',
        { expiresIn: '1h' }
      );

      return res.json({
        message: 'Login successful',
        role: user.role,
        token
      });
    } catch (jwtErr) {
      console.error('❌ JWT error:', jwtErr);
      return res.status(500).json({ message: 'Token generation failed', error: jwtErr.message });
    }
  });
});

module.exports = router;
