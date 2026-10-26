// One browser build qualifies consent actions and generic profile retirement.
import 'profile_smoke_test.dart' as profile;
import 'family_forms_smoke_test.dart' as consents;

void main() {
  profile.main();
  consents.main();
}
