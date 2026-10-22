import 'package:eschool_session/src/context/me_context_cubit.dart';
import 'package:eschool_session/src/deep_links/deep_link_parser.dart';
import 'package:eschool_session/src/selection/school_selection_cubit.dart';

/// What a push asks to open: its `link`, an app path such as `/my-school`
/// or `/invite/<token>`, and `school_id`, the school it belongs to. The
/// apps switch to that school before opening the link.
class PushLink {
  const PushLink({required this.target, this.schoolId});

  /// The link of a push's [data], or `null` when it has none the app
  /// knows ([parser] maps it to a screen).
  static PushLink? fromData(
    Map<String, dynamic> data, {
    required DeepLinkParser parser,
  }) {
    final Object? link = data['link'];
    if (link is! String || link.isEmpty) {
      return null;
    }
    final DeepLinkTarget? target = link.startsWith('/')
        ? parser.parsePath(link)
        : (Uri.tryParse(link) == null ? null : parser.parse(Uri.parse(link)));
    if (target == null) {
      return null;
    }
    final int? schoolId = int.tryParse('${data['school_id'] ?? ''}');
    return PushLink(
      target: target,
      schoolId: schoolId != null && schoolId > 0 ? schoolId : null,
    );
  }

  final DeepLinkTarget target;
  final int? schoolId;
}

/// Opens a tapped push's [link]: first switches to the school it belongs to
/// (when the user is at several and it is not the current one) and fetches
/// that school's `/me/context`, then hands the screen to [open].
Future<void> openPushLink(
  PushLink link, {
  required SchoolSelectionCubit schools,
  required MeContextCubit meContext,
  required void Function(DeepLinkTarget target) open,
}) async {
  final int? schoolId = link.schoolId;
  if (schoolId != null && await schools.select(schoolId)) {
    await meContext.load();
  }
  open(link.target);
}
