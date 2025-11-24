const mysql = require('mysql2');

const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',         // MySQL username
    password: '',         // MySQL password
    database: 'chefboynegro'
});

db.connect(err => {
    if (err) {
        console.error('❌ MySQL connection failed:', err);
        return;
    }
    console.log('✅ MySQL Connected...');
});

module.exports = db;
