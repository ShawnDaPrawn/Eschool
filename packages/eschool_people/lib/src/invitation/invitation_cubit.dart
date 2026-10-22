import 'package:equatable/equatable.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/invitation/invitation_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

enum InvitationStatus {
  loading,

  /// Unknown, expired or already used.
  invalid,
  ready,
  accepting,
  accepted,
  failure,
}

class InvitationState extends Equatable {
  const InvitationState({
    required this.status,
    this.invitation,
    this.error,
    this.signIn,
  });

  final InvitationStatus status;
  final GuardianInvitation? invitation;

  /// Why loading or accepting failed.
  final ApiException? error;

  /// The sign-in answer once accepted.
  final Map<String, dynamic>? signIn;

  @override
  List<Object?> get props => [status, invitation, error, signIn];
}

/// An invitation opened from its link: shows it, then accepts it (with a
/// new password when it needs one), which signs the guardian in.
class InvitationCubit extends Cubit<InvitationState> {
  InvitationCubit(this._repository, {required this.token})
    : super(const InvitationState(status: InvitationStatus.loading));

  final InvitationRepository _repository;
  final String token;

  /// The fewest characters a new password may have (the server checks its
  /// own rules too).
  static const int minimumPasswordLength = 8;

  Future<void> load() async {
    emit(const InvitationState(status: InvitationStatus.loading));
    try {
      final GuardianInvitation? invitation = await _repository.fetch(token);
      if (!isClosed) {
        emit(
          invitation == null
              ? const InvitationState(status: InvitationStatus.invalid)
              : InvitationState(
                  status: InvitationStatus.ready,
                  invitation: invitation,
                ),
        );
      }
    } catch (e) {
      if (!isClosed) {
        emit(
          InvitationState(
            status: InvitationStatus.failure,
            error: ApiException.fromException(e),
          ),
        );
      }
    }
  }

  Future<void> accept({String? password}) async {
    final GuardianInvitation? invitation = state.invitation;
    if (invitation == null || state.status == InvitationStatus.accepting) {
      return;
    }
    emit(
      InvitationState(
        status: InvitationStatus.accepting,
        invitation: invitation,
      ),
    );
    try {
      final Map<String, dynamic> signIn = await _repository.accept(
        token,
        password: invitation.needsPassword ? password : null,
      );
      if (!isClosed) {
        emit(
          InvitationState(
            status: InvitationStatus.accepted,
            invitation: invitation,
            signIn: signIn,
          ),
        );
      }
    } catch (e) {
      if (!isClosed) {
        emit(
          InvitationState(
            status: InvitationStatus.ready,
            invitation: invitation,
            error: ApiException.fromException(e),
          ),
        );
      }
    }
  }
}
