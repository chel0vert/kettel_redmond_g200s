import pexpect
import argparse
import json

parser = argparse.ArgumentParser()
parser.add_argument('--mac',         help='bluetooth MAC address')
parser.add_argument('--key',         help='magic key')
parser.add_argument('--command',     help='bluetooth command')
parser.add_argument('--timeout',     help='bluetooth timeout')
parser.add_argument('--mode',        help='mode of kettel command')
parser.add_argument('--temperature', help='temperature')

args = parser.parse_args()
if not args.mac or not args.command or not args.key:
    print json.dumps({'error': 'Wrong params', 'mac': args.mac})
    raise SystemExit(1)

iter    = '00'
key     = args.key
timeout = args.timeout or 3
child   = pexpect.spawn("gatttool -I -t random -b " + args.mac , ignore_sighup=False)

def hexToDec(chr):
    return int(str(chr), 16)

def auth(child):
    try:
        child.expect(r'\[LE\]>', timeout=timeout)
        child.sendline("connect")
        child.expect(r'Connection successful.*\[LE\]>', timeout=timeout)
        child.sendline("char-write-cmd 0x000c 0100")
        child.expect(r'\[LE\]>', timeout=timeout)
        child.sendline("char-write-req 0x000e 55" + iter + "ff" + key + "aa")
        child.expect("value:", timeout=timeout)
        child.expect("\r\n", timeout=timeout)
    except pexpect.exceptions.TIMEOUT:
        print json.dumps({'error': 'Timeout auth', 'mac': args.mac})
        raise SystemExit(1)
    connectedStr = child.before[0:].decode("utf-8")
    answer = connectedStr.split()[3]  # parse: 00 - no   01 - yes
    if '01' in answer:
        return 1
    else:
        print json.dumps({'error':'HOLD "+" on device ~10 sec. As you add the device to the app'})
        return 0
    pass

def runCommand(child, command, iter = '01'):
    result = ''
    try:
        child.expect(r'\[LE\]>', timeout=timeout)
        child.sendline("char-write-req 0x000e 55" + iter + command + "aa")
        child.expect("value: ", timeout=timeout)
        child.expect("\r\n", timeout=timeout)
        result = child.before[0:].decode("utf-8")
        child.expect(r'\[LE\]>', timeout=timeout)
    except pexpect.exceptions.TIMEOUT:
        print json.dumps({'error': 'Timeout', 'mac':args.mac,'command':command})
        raise SystemExit(1)
    return result

def getWatts(child):
    command = '4700'
    auth(child)
    statusStr = runCommand(child, command)
    result    = hexToDec(str(statusStr.split()[11] + statusStr.split()[10] + statusStr.split()[9]))
    meta = {
        'result' : 'Success',
        'watts'  : result,
        'message': statusStr,
    }
    return meta

def getCounts(child):
    command = '5000'
    auth(child)
    statusStr = runCommand(child, command)
    result    = hexToDec(str(statusStr.split()[7] + statusStr.split()[6]))
    meta = {
        'result' : 'Success',
        'counts' : result,
        'message': statusStr,
    }
    return meta

def sendOn(child):
    command = '03'
    auth(child)
    statusStr = runCommand(child, command)
    meta = {
        'result' : 'Success',
        'message': statusStr,
    }
    return meta

def sendOff(child):
    command = '04'
    auth(child)
    statusStr = runCommand(child, command)
    meta = {
        'result' : 'Success',
        'message': statusStr,
    }
    return meta

def getMode(child):
    command = '06'
    auth(child)
    statusStr = runCommand(child, command)
    answer    = statusStr.split()
    meta = {
        'status' : str(answer[11]),
        'temp'   : hexToDec(str(answer[8])),
        'mode'   : str(answer[3]),
    }
    return meta

def setMode(child):
    if not args.mode or not args.temperature:
        print json.dumps({'error':'wrong params'})
        raise SystemExit(1)
#    temp        = hex(int(args.temperature))
    temp = ('00'+hex(int(args.temperature))[2:])[-2:]
    mode        = args.mode # 00 - boiling, 01 heat , 03 night light
    howMuchBoil = '80'
    command = "05" + args.mode + "00" + temp + '00000000000000000000' + howMuchBoil + "0000"
    auth(child)
    statusStr = runCommand(child, command)
    meta = {
        'result' : 'Success',
        'message': statusStr,
    }
    return meta

def keepTemp(child):
    #if not args.mode or not args.temperature:
    #    print json.dumps({'error':'wrong params'})
    #    raise SystemExit(1)
    #temp        = hex(int(args.temperature))

    mode    = args.mode # 00 - boiling, 01 heat , 03 night light
#    temp    = hex(int( args.temperature))
    temp = ('00'+hex(int(args.temperature))[2:])[-2:]
    key     = args.key
    #command = "05" + mode + "00" + temp + '01'
    #howMuchBoil = hex(temp)
    command = "05" + '00' + '00'+ '00'  + "00"
    #command = '0500002800'
    auth(child)
    statusStr = runCommand(child, command)
    meta = {
        'result' : 'Success',
        'message': statusStr,
        'command': command,
    }
    return meta

if 'ON' in args.command:
    meta = sendOn(child)
elif 'OFF' in args.command:
    meta = sendOff(child)
elif 'STAT_WATTS' in args.command:
    meta = getWatts(child)
elif 'STAT_COUNTS' in args.command:
    meta = getCounts(child)
elif 'GET_MODE' in args.command:
    meta = getMode(child)
elif 'SET_MODE' in args.command:
    meta = setMode(child)
elif 'KEEP_TEMP' in args.command:
    meta = keepTemp(child)

result = {
    'result' : meta
}

print json.dumps(result)
child.sendline("exit")
