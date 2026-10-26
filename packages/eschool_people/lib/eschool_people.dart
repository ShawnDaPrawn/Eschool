/// The people screens both NavigateEd apps share, built once: a guardian's
/// approvals and invitation, My school, and the profile picture (the avatar
/// set or a photo). Each screen has its cubit and a repository interface,
/// with an implementation over the app's `ApiClient`.
library;

export 'src/approvals/approvals_cubit.dart';
export 'src/approvals/approvals_repository.dart';
export 'src/approvals/approvals_screen.dart';
export 'src/avatar/avatar_cubit.dart';
export 'src/avatar/avatar_repository.dart';
export 'src/avatar/avatar_screen.dart';
export 'src/common/learner_summary.dart' show LearnerSummary;
export 'src/handles/handles_repository.dart';
export 'src/handles/handles_cubit.dart';
export 'src/handles/handles_screen.dart';
export 'src/invitation/invitation_cubit.dart';
export 'src/invitation/invitation_repository.dart';
export 'src/invitation/invitation_screen.dart';
export 'src/my_school/my_school_cubit.dart';
export 'src/my_school/my_school_repository.dart';
export 'src/my_school/my_school_screen.dart';
export 'src/profile/profile_repository.dart';
export 'src/profile/profile_cubit.dart';
export 'src/profile/profile_screen.dart';

export 'src/profile/profile_file_field.dart'
    show ProfileFilePicker, ProfileFileSelector, pickProfileFile;

export 'src/profile/private_document_view.dart';
