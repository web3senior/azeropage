import { Title } from './helper/DocumentTitle'
import styles from './About.module.scss'

export default function About({ title }) {
  Title(title)

  return (
    <section className={styles.section}>
      <div className={`__container ms-motion-slideUpIn ${styles.container}`} data-width={`large`}>
        <div className={`card ms-depth-4 text-justify`}>
        <div className="card__header">{title}</div>
          <div className="card__body">

          </div>
        </div>
      </div>
    </section>
  )
}
