import 'dart:convert';

import 'package:eschool/data/models/attendanceDay.dart';
import 'package:eschool/data/models/customNotification.dart';
import 'package:eschool/data/models/exam.dart';
import 'package:eschool/data/models/fees.dart';
import 'package:eschool/data/models/feesTransaction.dart';
import 'package:eschool/data/models/paidFees.dart';
import 'package:eschool/data/models/parent.dart';
import 'package:eschool/data/models/result.dart';
import 'package:eschool/data/models/timeTableSlot.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool/utils/stripeService.dart';
import 'package:flutter/services.dart';

class StudentRepository {
  //get all dashboard data for students
  Future<Map<String, dynamic>> fetchStudentDashboard() async {
    return (await Api.get(
      url: Api.studentDashboard,
      useAuthToken: true,
    ))['data'];
  }

  Future<void> selectElectiveSubjects({
    required Map<int, List<int>> electedSubjectGroups,
  }) async {
    final electedSubjectGroupIds = electedSubjectGroups.keys
        .map((key) => {'id': key, 'subject_id': electedSubjectGroups[key]})
        .toList();

    final body = {'subject_group': electedSubjectGroupIds};
    await Api.post(
      url: Api.selectStudentElectiveSubjects,
      useAuthToken: true,
      body: body,
    );
  }

  Future<Map<String, dynamic>> fetchParentDetails() async {
    try {
      final result = await Api.get(
        url: Api.parentDetailsOfStudent,
        useAuthToken: true,
      );

      return {
        'mother':
            result['data']['mother'] != null &&
                result['data']['mother'].isNotEmpty
            ? Parent.fromJson(Map.from(result['data']['mother']))
            : null,
        'father':
            result['data']['father'] != null &&
                result['data']['father'].isNotEmpty
            ? Parent.fromJson(Map.from(result['data']['father']))
            : null,
        'guardian':
            result['data']['guardian'] != null &&
                result['data']['guardian'].isNotEmpty
            ? Parent.fromJson(Map.from(result['data']['guardian']))
            : null,
      };
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<List<TimeTableSlot>> fetchTimeTable({
    required bool useParentApi,
    required int childId,
  }) async {
    try {
      final result = await Api.get(
        url: useParentApi
            ? Api.getStudentTimetableParent
            : Api.studentTimeTable,
        useAuthToken: true,
        queryParameters: useParentApi ? {'child_id': childId} : null,
      );

      return (result['data'] as List)
          .map((e) => TimeTableSlot.fromJson(Map.from(e)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<Map<String, dynamic>> fetchExamResults({
    required bool useParentApi,
    required int childId,
    int? page,
    String? semesterId,
    int? sessionYearId,
  }) async {
    try {
      final Map<String, dynamic> queryParameters = {
        'page': page ?? 0,
        if (semesterId != null) 'semester_id': semesterId,
        if (sessionYearId != null) 'session_year_id': sessionYearId,
      };
      if (queryParameters['page'] == 0) {
        queryParameters.remove('page');
      }
      if (useParentApi) {
        queryParameters.addAll({'child_id': childId});
      }
      final result = await Api.get(
        url: useParentApi ? Api.getStudentResultsParent : Api.studentResults,
        useAuthToken: true,
        queryParameters: queryParameters,
      );

      return {
        'results': ((result['data'] ?? []) as List)
            .map((result) => Result.fromJson(Map.from(result)))
            .toList(),
        'currentPage': result['data'] is List
            ? 1
            : (result['data']?['current_page'] ?? 1),
        'totalPage': result['data'] is List
            ? 1
            : (result['data']?['last_page'] ?? 1),
      };
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<Map<String, dynamic>> fetchOnlineExamResults({
    required bool useParentApi,
    required int childId,
    int? page,
    int? sessionYearId,
    int? subjectId,
  }) async {
    try {
      // final Map<String, dynamic> queryParameters = {
      //   if (page != null && page > 0) 'page': page,
      //   if (sessionYearId != null) 'session_year_id': sessionYearId,
      //   if (subjectId != null && subjectId != 0) 'subject_id': subjectId,
      //   if (useParentApi) 'child_id': childId,
      // };

      // Mock data for now - replace with actual API call
      await Future.delayed(const Duration(milliseconds: 800));
      return {
        'results': [
          {
            'online_exam_id': 1,
            'title': '1. First Chapter',
            'exam_submitted_date': '05, Jul, 2025',
            'subject': {'id': 1, 'name': 'Mathematics', 'type': 'theory'},
            'total_marks': '100',
            'obtained_marks': '63',
          },
          {
            'online_exam_id': 2,
            'title': '1. First Chapter',
            'exam_submitted_date': '05, Jul, 2025',
            'subject': {'id': 1, 'name': 'Mathematics', 'type': 'theory'},
            'total_marks': '100',
            'obtained_marks': '63',
          },
        ],
        'currentPage': 1,
        'totalPage': 1,
      };
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<AttendanceReport> fetchAttendance({
    required int month,
    required int year,
    required bool useParentApi,
    required int childId,
  }) async {
    try {
      final Map<String, dynamic> queryParameters = {
        'month': month,
        'year': year,
      };

      if (useParentApi) {
        queryParameters.addAll({'child_id': childId});
      }

      final result = await Api.get(
        url: useParentApi
            ? Api.getStudentAttendanceParent
            : Api.getStudentAttendance,
        queryParameters: queryParameters,
        useAuthToken: true,
      );

      return AttendanceReport.fromJson(
        Map<String, dynamic>.from(result['data'] ?? {}),
      );
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  //
  //This method is used to fetch exams list
  Future<List<Exam>> fetchExamsList({
    required bool useParentApi,
    required int childId,
    required int examStatus,
    bool getTimetable = false,
    String? semesterId,
  }) async {
    try {
      final result = await Api.get(
        url: useParentApi ? Api.getStudentExamListParent : Api.studentExamList,
        useAuthToken: true,
        queryParameters: useParentApi
            ? {
                'child_id': childId,
                'status': examStatus,
                if (getTimetable) 'get_timetable': '1',
                if (semesterId != null) 'semester_id': semesterId,
              }
            : {
                'status': examStatus,
                if (getTimetable) 'get_timetable': '1',
                if (semesterId != null) 'semester_id': semesterId,
              },
      );

      return (result['data'] as List)
          .map((e) => Exam.fromExamJson(Map.from(e)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  //
  //This method is used to fetch time-table of particular exam
  Future<List<ExamTimeTable>> fetchExamTimeTable({
    required bool useParentApi,
    required int childId,
    required int examId,
  }) async {
    try {
      final result = await Api.get(
        url: useParentApi
            ? Api.getStudentExamDetailsParent
            : Api.studentExamDetails,
        useAuthToken: true,
        queryParameters: useParentApi
            ? {'child_id': childId, 'exam_id': examId}
            : {'exam_id': examId},
      );

      return (result['data'] as List)
          .map((e) => ExamTimeTable.fromJson(Map.from(e)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<List<PaidFees>> fetchFeesList({required int childId}) async {
    try {
      final result = await Api.get(
        url: Api.getPaidFeesListParent,
        useAuthToken: true,
        queryParameters: {'child_id': childId},
      );

      return (result['data'] as List)
          .map((e) => PaidFees.fromJson(Map.from(e)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<Uint8List> downloadFeesReceipt({int? feesPaidId}) async {
    //if feesPaidId id is not provided, it assumes that the student/child himself is download receipt

    final result = await Api.get(
      url: feesPaidId == null
          ? Api.downloadFeesPaidReceiptStudent
          : Api.downloadFeesPaidReceiptParent,
      useAuthToken: true,
      queryParameters: {if (feesPaidId != null) 'fees_paid_id': feesPaidId},
    );
    return base64Decode(result['pdf']);
  }

  Future<ChildFees> fetchDetailedFees({int? childId}) async {
    try {
      //if child id is not provided, it assumes that the student/child himself is paying the fees
      final result = await Api.get(
        url: childId == null
            ? Api.getStudentFeesDetailStudent
            : Api.getStudentFeesDetailParent,
        useAuthToken: true,
        queryParameters: {if (childId != null) 'child_id': childId},
      );
      return ChildFees.fromJson(result);
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<Map> addFeesTransaction({
    required double transactionAmount,
    int? childId,
    required int typeOfFee,
    required bool isFullyPaid,
    required double? paidDueCharges,
    double? compulsoryAmountPaid,
    double? dueChargesPaid,
    required int feesType, //0 = compulsory, 1 = installments, 2 = optional
    required List<FeesData> selectedFees,
    required int paymentMethod,
  }) async {
    final body = {
      if (childId != null) 'child_id': childId,
      'amount': transactionAmount.toStringAsFixed(2),
      'type_of_fee': typeOfFee,
      'is_fully_paid': isFullyPaid ? '1' : '0',
      'payment_method': paymentMethod,
      if (feesType == 0 && paidDueCharges != null)
        'due_charges': paidDueCharges.toStringAsFixed(2),
      if (feesType == 1)
        'installment_data': selectedFees
            .map(
              (e) => {
                'id': e.id,
                'name': e.name,
                'amount': e.amount!.toStringAsFixed(2),
                if (e.isDue && e.dueChargesAmount != null)
                  'due_charges': e.dueChargesAmount!.toStringAsFixed(2),
              },
            )
            .toList(),
      if (feesType == 2)
        'optional_fees_data': selectedFees
            .map((e) => {'id': e.id, 'amount': e.amount!.toStringAsFixed(2)})
            .toList(),
    };
    //if child id is not provided, it assumes that the student/child himself is paying the fees
    final result = await Api.post(
      body: body,
      url: childId == null
          ? Api.addFeesTransactionStudent
          : Api.addFeesTransaction,
      useAuthToken: true,
    );
    return result['payment_gateway_details'];
  }

  Future<void> storeFees({
    required String transactionId,
    int? childId,
  }) async {
    try {
      //if child id is not provided, it assumes that the student/child himself is paying the fees
      await Api.post(
        url: childId == null ? Api.storeFeesStudent : Api.storeFeesParent,
        useAuthToken: true,
        body: {
          'transaction_id': transactionId,
          if (childId != null) 'child_id': childId,
        },
      );
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> failPaymentTransaction({
    required String transactionId,
    required bool isStudentPayingFees,
  }) async {
    try {
      await Api.post(
        url: isStudentPayingFees
            ? Api.failPaymentTransactionStudent
            : Api.failPaymentTransaction,
        body: {'payment_transaction_id': transactionId},
        useAuthToken: true,
      );
    } catch (_) {}
  }

  Future<Map<String, dynamic>> fetchFeesTransactions({
    required int page,
    required bool isStudentLoggedIn,
    int? childId,
    int? sessionYearId,
  }) async {
    try {
      final Map<String, dynamic> queryParams = {'page': page};
      if (childId != null && childId != 0) {
        queryParams['child_id'] = childId;
      }
      if (sessionYearId != null) {
        queryParams['session_year_id'] = sessionYearId;
      }

      final response = await Api.get(
        url: isStudentLoggedIn
            ? Api.getFeesTransactionsStudent
            : Api.getFeesTransactions,
        useAuthToken: true,
        queryParameters: queryParams,
      );

      final List<FeesTransaction> fetchedTransactions = [];

      final data = response['data'];
      if (data != null && data['transaction-data'] != null) {
        final transactionData = data['transaction-data'] as List;
        for (int i = 0; i < transactionData.length; i++) {
          fetchedTransactions.add(FeesTransaction.fromJson(transactionData[i]));
        }
      }

      return {
        'feesTransactions': fetchedTransactions,
        'currentPage': data != null ? data['current_page'] ?? 1 : 1,
        'totalPage': data != null ? data['last_page'] ?? 1 : 1,
      };
    } catch (error) {
      throw ApiException(error.toString());
    }
  }

  Future<String> confirmStripePayment({
    required String paymentIntentId,
    required bool isStudentLoggedIn,
  }) async {
    try {
      final response = await Api.post(
        url: isStudentLoggedIn
            ? Api.verifyStripePaymentStudent
            : Api.verifyStripePayment,
        useAuthToken: true,
        body: {'payment_intent_id': paymentIntentId},
      );
      final statusOfTransaction = (response['data'] ?? '').toString();
      return statusOfTransaction;
    } on PlatformException catch (err) {
      throw ApiException(
        StripeService.getPlatformExceptionErrorResult(err).message ??
            ErrorMessageKeysAndCode.defaultErrorMessageCode,
      );
    } catch (error) {
      throw ApiException(ErrorMessageKeysAndCode.defaultErrorMessageCode);
    }
  }

  //get custom notifications for student
  Future<Map<String, dynamic>> fetchNotifications({required int page}) async {
    try {
      final response = await Api.get(
        url: Api.getStudentNotifications,
        useAuthToken: true,
        queryParameters: {'page': page},
      );

      final List<CustomNotification> notifications = [];

      for (int i = 0; i < response['data']['data'].length; i++) {
        notifications.add(
          CustomNotification.fromJson(response['data']['data'][i]),
        );
      }

      return {
        'notifications': notifications,
        'currentPage': response['data']['current_page'],
        'totalPage': response['data']['last_page'],
      };
    } catch (error) {
      throw ApiException(error.toString());
    }
  }

  //to send notification to parents/guardians to pay fees
  Future<void> askParentsToPayFees() async {
    await Api.get(url: Api.askParentsToPayFees, useAuthToken: true);
  }
}
