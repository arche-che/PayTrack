-- Run once on an existing PayTrack database before accepting payments with the updated code.
ALTER TABLE `payments`
  ADD COLUMN `enrolled_subjects_snapshot` LONGTEXT NULL AFTER `notes`;
