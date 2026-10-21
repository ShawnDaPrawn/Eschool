import 'package:eschool_teacher/data/models/student.dart';
import 'package:eschool_teacher/ui/widgets/studentAttendanceTileContainer.dart';
import 'package:eschool_teacher/ui/widgets/studentTileContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:eschool_teacher/data/models/attendanceEnums.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

class SearchStudentScreen extends StatefulWidget {
  const SearchStudentScreen({
    required this.fromAttendanceScreen,
    required this.students,
    required this.leaveDetails,
    super.key,
    this.listOfAttendanceReport,
  });
  final List<Student> students;
  final bool fromAttendanceScreen;
  final Map<int, AttendanceLeaveType> leaveDetails;
  final List<Map<int, AttendanceType?>>? listOfAttendanceReport;

  @override
  State<SearchStudentScreen> createState() => _SearchStudentScreenState();

  static Route<List<Map<int, AttendanceType?>>?> route(
    RouteSettings routeSettings,
  ) {
    final arguments = routeSettings.arguments! as Map<String, dynamic>;
    return CupertinoPageRoute(
      builder: (_) => SearchStudentScreen(
        fromAttendanceScreen: arguments['fromAttendanceScreen'],
        students: arguments['students'],
        listOfAttendanceReport: arguments['listOfAttendanceReport'],
        leaveDetails:
            arguments['onLeaveStudents'] ?? <int, AttendanceLeaveType>{},
      ),
    );
  }
}

class _SearchStudentScreenState extends State<SearchStudentScreen> {
  late final TextEditingController searchQueryTextEditingController =
      TextEditingController()..addListener(searchQueryTextControllerListener);

  bool searchTextEmpty = false;
  late List<Student> searchedStudents = [];

  late List<Map<int, AttendanceType?>> listOfAttendance =
      widget.fromAttendanceScreen ? widget.listOfAttendanceReport! : [];

  void _updateAttendance(int studentId, AttendanceType? newType) {
    final index = listOfAttendance.indexWhere(
      (element) => element.keys.first == studentId,
    );
    listOfAttendance[index][studentId] = newType;
    setState(() {});
  }

  // Timer? waitForNextSearchRequestTimer;

  // int waitForNextRequestSearchQueryTimeInMilliSeconds = 500;

  void searchQueryTextControllerListener() {
    //waitForNextSearchRequestTimer?.cancel();
    //setWaitForNextSearchRequestTimer();
    if (searchQueryTextEditingController.text.isNotEmpty) {
      searchedStudents.clear();
      searchedStudents.addAll(
        widget.students.where(
          (element) => element.getFullName().toLowerCase().contains(
            searchQueryTextEditingController.text.trim().toLowerCase(),
          ),
        ),
      );
      if (searchTextEmpty) {
        searchTextEmpty = false;
      }
      setState(() {});
    } else {
      if (!searchTextEmpty) {
        setState(() {
          searchTextEmpty = true;
        });
      }
    }
  }

  @override
  void dispose() {
    searchQueryTextEditingController.dispose();
    super.dispose();
  }

  Widget _buildSearchTextField() {
    return TextField(
      controller: searchQueryTextEditingController,
      autofocus: true,
      cursorColor: Theme.of(context).colorScheme.onInverseSurface,
      style: TextStyle(color: Theme.of(context).colorScheme.onInverseSurface),
      decoration: InputDecoration(
        hintStyle: TextStyle(
          color: Theme.of(context).colorScheme.onInverseSurface,
        ),
        border: InputBorder.none,
        hintText: UiUtils.getTranslatedLabel(context, searchStudentKey),
      ),
    );
  }

  Widget _buildStudents() {
    return searchTextEmpty
        ? Center(
            child: Text(
              UiUtils.getTranslatedLabel(context, searchStudentKey),
              style: const TextStyle(fontSize: 16),
            ),
          )
        : searchedStudents.isEmpty
        ? Center(
            child: Text(
              UiUtils.getTranslatedLabel(context, noStudentsFoundKey),
              style: const TextStyle(fontSize: 16),
            ),
          )
        : ListView.builder(
            clipBehavior: Clip.none,
            padding: EdgeInsets.symmetric(
              vertical: 20,
              horizontal:
                  MediaQuery.sizeOf(context).width *
                  UiUtils.screenContentHorizontalPaddingPercentage,
            ),
            itemCount: searchedStudents.length,
            itemBuilder: (context, index) {
              if (widget.fromAttendanceScreen) {
                final attendanceType = listOfAttendance
                    .where(
                      (element) =>
                          element.keys.first == searchedStudents[index].id,
                    )
                    .toList()
                    .first[searchedStudents[index].id];

                return StudentAttendanceTileContainer(
                  attendanceType: attendanceType,
                  student: searchedStudents[index],
                  updateAttendance: _updateAttendance,
                  isOnLeave: widget.leaveDetails.containsKey(
                    searchedStudents[index].id,
                  ),
                  leaveType: widget.leaveDetails[searchedStudents[index].id],
                );
              }

              return StudentTileContainer(student: searchedStudents[index]);
            },
          );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, dynamic result) {
        if (didPop) {
          return;
        }
        Navigator.of(context).pop(listOfAttendance);
      },
      child: Scaffold(
        appBar: AppBar(
          actions: [
            IconButton(
              iconSize: 26,
              color: Theme.of(context).colorScheme.onInverseSurface,
              onPressed: () {
                searchQueryTextEditingController.clear();
                searchedStudents.clear();
                setState(() {});
              },
              icon: const Icon(Icons.clear),
            ),
          ],
          title: _buildSearchTextField(),
          leading: Padding(
            padding: const EdgeInsets.all(17),
            child: SvgButton(
              onTap: () {
                Navigator.of(context).pop(listOfAttendance);
              },
              svgIconUrl: UiUtils.getBackButtonPath(context),
              tooltip: MaterialLocalizations.of(context).backButtonTooltip,
            ),
          ),
          backgroundColor: Theme.of(context).colorScheme.inverseSurface,
        ),
        body: _buildStudents(),
      ),
    );
  }
}
