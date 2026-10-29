import 'package:equatable/equatable.dart';

/// A request's credentials and school; it is never logged or used as authority.
class SessionIdentity extends Equatable {
  const SessionIdentity({
    required this.generation,
    required this.token,
    this.schoolId,
    this.browserSession,
  });

  final int generation;
  final String token;
  final int? schoolId;
  final String? browserSession;

  /// Learning the school from the first successful context does not replace
  /// that context. A real switch, new credentials or another cookie does.
  bool resolvesInitialSchool(SessionIdentity next, int? contextSchool) =>
      schoolId == null &&
      contextSchool != null &&
      next.schoolId == contextSchool &&
      generation == next.generation &&
      token == next.token &&
      browserSession == next.browserSession;

  @override
  List<Object?> get props => [generation, token, schoolId, browserSession];

  @override
  bool get stringify => false;
}
