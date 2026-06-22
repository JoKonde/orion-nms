import { Badge, healthGradeLabel, healthGradeVariant } from '../ui/Badge';

/**
 * HealthGauge — jauge circulaire du score sante reseau (0-100).
 */
export function HealthGauge({ score = 0, grade = 'good' }) {
  const clamped = Math.max(0, Math.min(100, score));
  const circumference = 2 * Math.PI * 54;
  const offset = circumference - (clamped / 100) * circumference;

  return (
    <div className={`health-gauge health-gauge--${grade}`}>
      <svg className="health-gauge__ring" viewBox="0 0 120 120" aria-hidden="true">
        <circle className="health-gauge__track" cx="60" cy="60" r="54" />
        <circle
          className="health-gauge__progress"
          cx="60"
          cy="60"
          r="54"
          strokeDasharray={circumference}
          strokeDashoffset={offset}
        />
      </svg>
      <div className="health-gauge__center">
        <span className="health-gauge__score">{clamped}</span>
        <span className="health-gauge__unit">/ 100</span>
      </div>
      <Badge variant={healthGradeVariant(grade)} className="health-gauge__badge">
        {healthGradeLabel(grade)}
      </Badge>
    </div>
  );
}
