import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart' as platform;

/// Build selection is never a school role or an authorization grant.
class StaffBuildIdentity {
  const StaffBuildIdentity._(this.name, this.valid);

  factory StaffBuildIdentity.resolve({
    String? nativeFlavor,
    String dartFlavor = '',
  }) {
    final native = nativeFlavor ?? '';
    final name = native.isNotEmpty
        ? native
        : dartFlavor.isNotEmpty
        ? dartFlavor
        : 'teacher';
    final known = const {'teacher', 'facilitator', 'vendor'}.contains(name);
    return StaffBuildIdentity._(
      name,
      known && (native.isEmpty || dartFlavor.isEmpty || native == dartFlavor),
    );
  }

  static StaffBuildIdentity get current => StaffBuildIdentity.resolve(
    nativeFlavor: platform.appFlavor ?? (kIsWeb ? null : 'teacher'),
    dartFlavor: AppConfig.flavor,
  );

  final String name;
  final bool valid;

  /// Reserved shells cannot initialize teacher auth, routes or repositories.
  bool get canStartTeacher => valid && name == 'teacher';

  bool get canStartVendor => valid && name == 'vendor';

  bool get canStartFacilitator => valid && name == 'facilitator';

  String get displayName => valid
      ? switch (name) {
          'facilitator' => 'NavigateEd Facilitator',
          'vendor' => 'NavigateEd Vendor',
          _ => 'NavigateEd Teacher',
        }
      : 'NavigateEd';
}

Future<void> startStaffBuild({
  required StaffBuildIdentity identity,
  required Future<void> Function() startTeacher,
  Future<void> Function()? startFacilitator,
  Future<void> Function()? startVendor,
  required void Function(StaffBuildIdentity) startUnavailable,
}) async {
  if (identity.canStartTeacher) {
    await startTeacher();
  } else if (identity.canStartFacilitator && startFacilitator != null) {
    await startFacilitator();
  } else if (identity.canStartVendor && startVendor != null) {
    await startVendor();
  } else {
    startUnavailable(identity);
  }
}
