//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'open_personal_wallet_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class OpenPersonalWalletRequest {
  /// Returns a new [OpenPersonalWalletRequest] instance.
  OpenPersonalWalletRequest({
    required this.operationId,

    required this.ownerUserId,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'owner_user_id', required: true, includeIfNull: false)
  final int ownerUserId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is OpenPersonalWalletRequest &&
          other.operationId == operationId &&
          other.ownerUserId == ownerUserId;

  @override
  int get hashCode => operationId.hashCode + ownerUserId.hashCode;

  factory OpenPersonalWalletRequest.fromJson(Map<String, dynamic> json) =>
      _$OpenPersonalWalletRequestFromJson(json);

  Map<String, dynamic> toJson() => _$OpenPersonalWalletRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
