//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_reminded.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentReminded {
  /// Returns a new [ReenrolmentReminded] instance.
  ReenrolmentReminded({
    required this.contractId,

    required this.learners,

    required this.notices,

    required this.more,
  });

  // minimum: 1
  @JsonKey(name: r'contract_id', required: true, includeIfNull: false)
  final int contractId;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final int learners;

  @JsonKey(name: r'notices', required: true, includeIfNull: false)
  final int notices;

  /// More guardians still wait for today's reminder; send again to continue.
  @JsonKey(name: r'more', required: true, includeIfNull: false)
  final bool more;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentReminded &&
          other.contractId == contractId &&
          other.learners == learners &&
          other.notices == notices &&
          other.more == more;

  @override
  int get hashCode =>
      contractId.hashCode +
      learners.hashCode +
      notices.hashCode +
      more.hashCode;

  factory ReenrolmentReminded.fromJson(Map<String, dynamic> json) =>
      _$ReenrolmentRemindedFromJson(json);

  Map<String, dynamic> toJson() => _$ReenrolmentRemindedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
