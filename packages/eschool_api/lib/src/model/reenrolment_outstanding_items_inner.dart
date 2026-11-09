//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reenrolment_outstanding_items_inner_awaiting_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_outstanding_items_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentOutstandingItemsInner {
  /// Returns a new [ReenrolmentOutstandingItemsInner] instance.
  ReenrolmentOutstandingItemsInner({
    required this.learnerId,

    required this.name,

    required this.acceptedCount,

    required this.feeResponsibleCount,

    required this.awaiting,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'accepted_count', required: true, includeIfNull: false)
  final int acceptedCount;

  @JsonKey(name: r'fee_responsible_count', required: true, includeIfNull: false)
  final int feeResponsibleCount;

  @JsonKey(name: r'awaiting', required: true, includeIfNull: false)
  final List<ReenrolmentOutstandingItemsInnerAwaitingInner> awaiting;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentOutstandingItemsInner &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.acceptedCount == acceptedCount &&
          other.feeResponsibleCount == feeResponsibleCount &&
          other.awaiting == awaiting;

  @override
  int get hashCode =>
      learnerId.hashCode +
      name.hashCode +
      acceptedCount.hashCode +
      feeResponsibleCount.hashCode +
      awaiting.hashCode;

  factory ReenrolmentOutstandingItemsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$ReenrolmentOutstandingItemsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ReenrolmentOutstandingItemsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
