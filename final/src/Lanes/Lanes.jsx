import styles from './Lanes.module.css'

function Lanes() {
  return (
    <div className={styles.lanesContainer}>
      <button className={styles.button}><a href="#">Lane 1</a></button>
      <button className={styles.button}><a href="#">Lane 2</a></button>
      <button className={styles.button}><a href="#">Lane 3</a></button>
      <button className={styles.button}><a href="#">Lane 4</a></button>
      <button className={styles.button}><a href="#">Lane 5</a></button>
    </div>
  );
}

export default Lanes