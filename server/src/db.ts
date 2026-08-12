import mysql from 'mysql2/promise';

const pool = mysql.createPool({
  host: 'nj.mhuany.xyz',
  port: 3306,
  user: 'sersle53cq41jg0',
  password: 'J1J1SHIDVIK2',
  database: 'sersle53cq41jg0',
  waitForConnections: true,
  connectionLimit: 10,
  charset: 'utf8mb4',
});

export { pool };
export default pool;
